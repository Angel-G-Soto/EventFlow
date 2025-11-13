<?php
/**
 * EventCompletionServiceTest
 *
 * Purpose:
 *   Verify the rule:
 *     - approved + end_time in the past  => mark completed (+ optional audit)
 *     - otherwise                        => no change
 *
 * We mock the Eloquent builder chain to avoid DB I/O:
 *   Event::newQuery() → where('id', X) → first([...])
 *   Event::newQuery() → where('id', X) → update(['status' => ...])
 */

use App\Models\Event;
use App\Services\AuditService;
use App\Services\EventCompletionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
//use Mockery;

beforeEach(function () {
    Carbon::setTestNow('2025-10-28 10:00:00');
});

afterEach(function () {
    Mockery::close();
});

function fakeEventModel(int $id, string $status, ?string $endTime): Model {
    // Create a partial mock of Eloquent Model that responds to getAttribute().
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $model->shouldReceive('getAttribute')->with('status')->andReturn($status);
    $model->shouldReceive('getAttribute')->with('end_time')->andReturn($endTime);
    return $model;
}

it('completes an approved event whose end_time has passed', function () {
    // Arrange: mock Event model and 2 builder chains (first & update)
    $eventModel = Mockery::mock(Event::class)->makePartial();

    // First chain: load snapshot
    $firstBuilder = Mockery::mock(Builder::class);
    $firstBuilder->shouldReceive('where')->once()->with('id', 1001)->andReturnSelf();
    $firstBuilder->shouldReceive('first')
        ->once()
        ->with(['id', 'status', 'end_time'])
        ->andReturn(fakeEventModel(1001, 'approved', '2025-10-28 09:00:00'));

    // Second chain: update status
    $updateBuilder = Mockery::mock(Builder::class);
    $updateBuilder->shouldReceive('where')->once()->with('id', 1001)->andReturnSelf();
    $updateBuilder->shouldReceive('update')
        ->once()
        ->with(['status' => EventCompletionService::STATUS_COMPLETED])
        ->andReturn(1);

    // Event::newQuery() will be called twice: first(), then update()
    $eventModel->shouldReceive('newQuery')
        ->times(2)
        ->andReturn($firstBuilder, $updateBuilder);

    // Audit
    $audit = Mockery::mock(AuditService::class);
    config()->set('eventflow.system_user_id', 1);
    $audit->shouldReceive('logAdminAction')
        ->once()
        ->with(1, 'EVENT_COMPLETED_AUTO', 'Event', 1001);

    $svc = new EventCompletionService($eventModel, $audit);

    // Act + Assert
    expect($svc->completeIfPast(1001))->toBeTrue();
});

it('does nothing when event is not approved', function () {
    $eventModel = Mockery::mock(Event::class)->makePartial();

    $firstBuilder = Mockery::mock(Builder::class);
    $firstBuilder->shouldReceive('where')->once()->with('id', 1002)->andReturnSelf();
    $firstBuilder->shouldReceive('first')
        ->once()
        ->with(['id', 'status', 'end_time'])
        ->andReturn(fakeEventModel(1002, 'completed', '2025-10-28 08:00:00'));

    $eventModel->shouldReceive('newQuery')->once()->andReturn($firstBuilder);

    $audit = Mockery::mock(AuditService::class);
    $audit->shouldReceive('logAdminAction')->never();

    $svc = new EventCompletionService($eventModel, $audit);

    expect($svc->completeIfPast(1002))->toBeFalse();
});

it('does nothing when end_time is in the future', function () {
    $eventModel = Mockery::mock(Event::class)->makePartial();

    $firstBuilder = Mockery::mock(Builder::class);
    $firstBuilder->shouldReceive('where')->once()->with('id', 1003)->andReturnSelf();
    $firstBuilder->shouldReceive('first')
        ->once()
        ->with(['id', 'status', 'end_time'])
        ->andReturn(fakeEventModel(1003, 'approved', '2025-10-28 11:00:00'));

    $eventModel->shouldReceive('newQuery')->once()->andReturn($firstBuilder);

    $audit = Mockery::mock(AuditService::class);
    $audit->shouldReceive('logAdminAction')->never();

    $svc = new EventCompletionService($eventModel, $audit);

    expect($svc->completeIfPast(1003))->toBeFalse();
});

it('returns false when event is not found', function () {
    $eventModel = Mockery::mock(Event::class)->makePartial();

    $firstBuilder = Mockery::mock(Builder::class);
    $firstBuilder->shouldReceive('where')->once()->with('id', 9999)->andReturnSelf();
    $firstBuilder->shouldReceive('first')
        ->once()
        ->with(['id', 'status', 'end_time'])
        ->andReturn(null); // not found

    $eventModel->shouldReceive('newQuery')->once()->andReturn($firstBuilder);

    $audit = Mockery::mock(AuditService::class);
    $audit->shouldReceive('logAdminAction')->never();

    $svc = new EventCompletionService($eventModel, $audit);

    expect($svc->completeIfPast(9999))->toBeFalse();
});
