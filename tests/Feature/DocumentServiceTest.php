<?php

use App\Services\DocumentService;
use App\Models\Document;
use App\Models\Event;
use App\Exceptions\StorageException;
use App\Jobs\ProcessFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Filesystem\FileNotFoundException;


beforeEach(function () {
    $this->service = new DocumentService();
    Storage::fake('uploads_temp');
    Storage::fake('documents');
    Queue::fake();
});

describe('handleUpload', function () {
    it('successfully handles file upload', function () {
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $document = $this->service->handleUpload($file, 1, $event->id);

        expect($document)->toBeInstanceOf(Document::class)
            ->and($document->event_id)->toBe($event->id)
            ->and($document->name)->not->toBeEmpty();

        expect(Storage::disk('uploads_temp')->exists($document->name))->toBeTrue();
        Queue::assertNothingPushed();
    });

    it('throws StorageException when temp storage fails', function () {
        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('putFileAs')->andReturn(false);
        Storage::shouldReceive('disk')->with('uploads_temp')->andReturn($mockDisk);

        $file = UploadedFile::fake()->create('test.pdf');

        expect(fn() => $this->service->handleUpload($file, 1, 1))
            ->toThrow(StorageException::class, 'Failed to write temporary file.');
    });
});

describe('dispatchVirusScanForDocuments', function () {
    it('dispatches scan for entire batch and notifies when requested', function () {
        $event = Event::factory()->create();
        $documents = Document::factory()->count(2)->for($event)->create();

        $this->service->dispatchVirusScanForDocuments($event, $documents->pluck('id')->all(), true);

        Queue::assertPushed(ProcessFileUpload::class, function ($job) use ($documents, $event) {
            $ref = new ReflectionClass($job);
            $documentsProp = $ref->getProperty('documents');
            $documentsProp->setAccessible(true);
            $eventProp = $ref->getProperty('eventId');
            $eventProp->setAccessible(true);
            $notifyProp = $ref->getProperty('notifyApproverOnClean');
            $notifyProp->setAccessible(true);

            $batch = $documentsProp->getValue($job);

            return $batch->pluck('id')->sort()->values()->all() === $documents->pluck('id')->sort()->values()->all()
                && $eventProp->getValue($job) === $event->id
                && $notifyProp->getValue($job) === true;
        });
    });

    it('does not dispatch when batch is empty and notification disabled', function () {
        $event = Event::factory()->create();

        $this->service->dispatchVirusScanForDocuments($event, []);

        Queue::assertNothingPushed();
    });

    it('dispatches notification-only job when batch empty but notification requested', function () {
        $event = Event::factory()->create();

        $this->service->dispatchVirusScanForDocuments($event, [], true);

        Queue::assertPushed(ProcessFileUpload::class, function ($job) use ($event) {
            $ref = new ReflectionClass($job);
            $documentsProp = $ref->getProperty('documents');
            $documentsProp->setAccessible(true);
            $eventProp = $ref->getProperty('eventId');
            $eventProp->setAccessible(true);

            return $documentsProp->getValue($job)->count() === 0
                && $eventProp->getValue($job) === $event->id;
        });
    });
});

describe('deleteDocument', function () {
    it('successfully deletes document and file', function () {
        $document = Document::factory()->create(['file_path' => 'test/file.pdf']);
        Storage::disk('documents')->put('test/file.pdf', 'content');

        $result = $this->service->deleteDocument($document);

        expect($result)->toBeTrue();
        expect(Storage::disk('documents')->exists('test/file.pdf'))->toBeFalse();
        expect(Document::find($document->id))->toBeNull();
    });

    it('treats missing physical file as deleted and removes DB row', function () {
        // File does not exist on disk by default with fake disk
        $document = Document::factory()->create(['file_path' => 'missing/file.pdf']);

        $result = $this->service->deleteDocument($document);

        expect($result)->toBeTrue();
        expect(Document::find($document->id))->toBeNull();
    });

    it('handles missing file gracefully', function () {
        $document = Document::factory()->withoutFilePath()->create();

        $result = $this->service->deleteDocument($document);

        expect($result)->toBeTrue();
        expect(Document::find($document->id))->toBeNull();
    });

    it('throws StorageException on storage error', function () {
        $document = Document::factory()->create(['file_path' => 'test.pdf']);
        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('exists')->andThrow(new Exception('Storage error'));
        Storage::shouldReceive('disk')->with('documents')->andReturn($mockDisk);

        expect(fn() => $this->service->deleteDocument($document))
            ->toThrow(StorageException::class, 'Storage driver error while deleting file.');
    });

    it('throws StorageException when delete operation returns false', function () {
        $document = Document::factory()->create(['file_path' => 'stubborn.pdf']);
        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('exists')->andReturn(true);
        $mockDisk->shouldReceive('delete')->andReturn(false);
        Storage::shouldReceive('disk')->with('documents')->andReturn($mockDisk);

        expect(fn() => $this->service->deleteDocument($document))
            ->toThrow(StorageException::class, 'Storage driver error while deleting file.');
    });
});

describe('getDocumentStream', function () {
    it('returns valid stream for existing file', function () {
        $document = Document::factory()->create(['file_path' => 'test.pdf']);
        Storage::disk('documents')->put('test.pdf', 'file content');

        $stream = $this->service->getDocumentStream($document);

        expect($stream)->toBeResource();
        fclose($stream);
    });

    it('throws FileNotFoundException for missing file', function () {
        $document = Document::factory()->create(['file_path' => 'not-there.pdf']);

        expect(fn() => $this->service->getDocumentStream($document))
            ->toThrow(FileNotFoundException::class);
    });

    it('throws StorageException when readStream fails', function () {
        $document = Document::factory()->create(['file_path' => 'test.pdf']);
        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('exists')->andReturn(true);
        $mockDisk->shouldReceive('readStream')->andThrow(new Exception('Read error'));
        Storage::shouldReceive('disk')->with('documents')->andReturn($mockDisk);

        expect(fn() => $this->service->getDocumentStream($document))
            ->toThrow(StorageException::class, 'Failed to open document stream.');
    });

    it('throws StorageException for invalid stream', function () {
        $document = Document::factory()->create(['file_path' => 'test.pdf']);
        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('exists')->andReturn(true);
        $mockDisk->shouldReceive('readStream')->andReturn(false);
        Storage::shouldReceive('disk')->with('documents')->andReturn($mockDisk);

        expect(fn() => $this->service->getDocumentStream($document))
            ->toThrow(StorageException::class, 'Storage driver returned an invalid stream.');
    });
});
