<?php
/**
 * Verifies the job delegates to the service.
 */

use App\Jobs\CompleteEventJob;
use App\Services\EventCompletionService;
//use Mockery;

afterEach(function () {
    Mockery::close();
});

it('calls EventCompletionService::completeIfPast with the event id', function () {
    $svc = Mockery::mock(EventCompletionService::class);
    $svc->shouldReceive('completeIfPast')->once()->with(777)->andReturnTrue();

    $job = new CompleteEventJob(777);
    $job->handle($svc);

    expect(true)->toBeTrue();
});
