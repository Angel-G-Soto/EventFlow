<?php

use App\Jobs\ProcessFileUpload;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;

it('scans and moves a clean file using ClamAV', function () {

    $file_name = 'session.txt';
    $file_path = 'storage/app/tmp/uploads/'.$file_name;

    // Create file
    Storage::disk('uploads_temp')->put($file_name, 'Hello World!');

    // Create a fake Document
    $document = Document::factory()->create([
        'name' => $file_name,
        'file_path' => $file_path,
    ]);
    // Dispatch job directly
    $job = new ProcessFileUpload(new Collection([$document]));
    $job->handle();

    // Assert the file was moved
    Storage::disk('uploads_temp')->assertMissing($file_name);
    Storage::disk('documents')->assertExists($file_name);


});

it('scans and deletes a dangerous file using ClamAV', function () {

    $file_name = 'virus_test_file.txt';
    $file_path = 'storage/app/tmp/uploads/'.$file_name;

    // Create file
    Storage::disk('uploads_temp')->put($file_name, 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*');

    // Create a fake Document
    $document = Document::factory()->create([
        'name' => $file_name,
        'file_path' => $file_path,
    ]);

    // Dispatch job directly
    $job = new ProcessFileUpload(new Collection([$document]));
    $job->handle();

    // Assert the file was moved
    Storage::disk('uploads_temp')->assertMissing($file_name);
    Storage::disk('documents')->assertMissing($file_name);
});

it('process fails', function () {

    $file_name = 'virus_test_file.txt';
    $file_path = 'storage/app/tmp/uploads/'.$file_name;

    // Create a fake Document
    $document = Document::factory()->create([
        'name' => $file_name,
        'file_path' => $file_path,
    ]);

    // Dispatch job directly
    $job = new ProcessFileUpload(new Collection([$document]));
    $job->handle();
})->throws(ProcessFailedException::class);

it('scans and moves a clean file using ClamAV1', function () {

    $file_name = 'session.txt';
    $file_path = 'storage/app/tmp/uploads/'.$file_name; // Initial file path
    $new_file_path = 'documents/'.$file_name; // New file path after the file is moved

    // Create file
    Storage::disk('uploads_temp')->put($file_name, 'Hello World!');

    // Create a fake Document
    $document = Document::factory()->create([
        'name' => $file_name,
        'file_path' => $file_path, // This is the initial path
    ]);

    // Dispatch job directly
    $job = new ProcessFileUpload(new Collection([$document]));
    $job->handle();

    // Assert the file was moved
    Storage::disk('uploads_temp')->assertMissing($file_name);
    Storage::disk('documents')->assertExists($file_name);

    // Reload the document to ensure its file path is updated
    $document->refresh();

    // Assert that the file path is updated correctly in the Document model
    $this->assertEquals($new_file_path, $document->file_path);
});
