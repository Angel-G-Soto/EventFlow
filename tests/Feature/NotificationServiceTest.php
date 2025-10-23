<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;


it('dispatches approval required email', function () {
    Bus::fake();
    $notificationService = app(NotificationService::class);
    $email = 'venue_manager@upr.edu';
    $evenData = [
        'event_name' => 'Resume Workshop',
    ];
    $notificationService->dispatchApprovalRequiredNotification($email, $evenData);
    Bus::assertDispatched(\App\Jobs\SendApprovalRequiredEmailJob::class);

});

it('dispatches rejection email', function () {
    Bus::fake();
    $notificationService = app(NotificationService::class);
    $email = 'representative@upr.edu';
    $evenData = [
        'event_name' => 'Resume Workshop',
    ];
    $notificationService->dispatchRejectionNotification($email, $evenData,'Missing required documents' );
    Bus::assertDispatched(\App\Jobs\SendRejectionEmailJob::class);

});


it('dispatches sanction email', function () {
    Bus::fake();
    $notificationService = app(NotificationService::class);
    $email = 'representative@upr.edu';
    $evenData = [
        'event_name' => 'Resume Workshop',
    ];
    $notificationService->dispatchSanctionedNotification($email, $evenData,'Missing required documents' );
    Bus::assertDispatched(\App\Jobs\SendSanctionedEmailJob::class);
});

it('dispatches cancellation email', function () {
    Bus::fake();
    $notificationService = app(NotificationService::class);
    $emails = ['advisor@upr.edu','venue_manager@upr.edu','dean@upr.edu','dsca@upr.edu'];
    $evenData = [
        'event_name' => 'Resume Workshop',
    ];
    $notificationService->dispatchCancellationNotifications($emails, $evenData,'Have to cancel due to low attendance' );
    Bus::assertDispatched(\App\Jobs\SendCancelationEmailJob::class);
});

it('dispatches withdrawal email', function () {
    Bus::fake();
    $notificationService = app(NotificationService::class);
    $emails = ['advisor@upr.edu','venue_manager@upr.edu','dean@upr.edu','dsca@upr.edu'];
    $evenData = [
        'event_name' => 'Resume Workshop',
    ];
    $notificationService->dispatchWithdrawalNotifications($emails, $evenData,'Have to rescheduled due to logistic changes' );
    Bus::assertDispatched(\App\Jobs\SendWithdrawalEmailJob::class);
});

