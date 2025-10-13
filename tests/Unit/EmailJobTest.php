<?php


use App\Jobs\SendCancelationEmailJob;
use App\Jobs\SendRejectionEmailJob;
use \App\Jobs\SendApprovalRequiredEmailJob;
use App\Jobs\SendSanctionedEmailJob;
use App\Jobs\SendWithdrawalEmailJob;
use App\Mail\CancelationEmail;
use App\Mail\RejectionEmail;
use App\Mail\SanctionEmail;
use App\Mail\WithdrawalEmail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendAdvisorEmailJob;
use App\Mail\AdvisorEmail;
use Illuminate\Support\Facades\Queue;

//----------------------------------------------------------------------------------------------------------------------------------------------------------
//--------------------------------------Synchronous---------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------------------------------------------

//-----------Test Advisor email-----------------------------
it('send advisor email', function () {

    Mail::fake();

    $email = 'advisor@upr.edu';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $job = new SendAdvisorEmailJob($email, $eventData);
    $job->handle();
    Mail::assertSent(AdvisorEmail::class, function (AdvisorEmail $mail) use ($job) {
        if (!$mail->hasTo($job->email)) {
            return false;
        }
        return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
    });
});

//-----------Test Venue Manager/DSCA/Dean of Administration email-----------------------------
it('send approval required (Venue Manager/DSCA/Dean of Administration) email', function () {

    Mail::fake();

    $email = 'approver@upr.edu';
    $approverType = 'venue';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];

    //Venue Manager
    $job = new SendApprovalRequiredEmailJob($email,$approverType, $eventData);
    $job->handle();

    Mail::assertSent(\App\Mail\VenueManagerEmail::class, function (\App\Mail\VenueManagerEmail $mail) use ($job) {
        if (!$mail->hasTo($job->approverEmail)) {
            return false;
        }

        return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
    });

    //DSCA
    $approverType = 'dsca';
    $job = new SendApprovalRequiredEmailJob($email,$approverType, $eventData);
    $job->handle();

    Mail::assertSent(\App\Mail\DscaEmail::class, function (\App\Mail\DscaEmail $mail) use ($job) {
        if (!$mail->hasTo($job->approverEmail)) {
            return false;
        }

        return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
    });

    //Dean of Administration
    $approverType = 'dean';
    $job = new SendApprovalRequiredEmailJob($email,$approverType, $eventData);
    $job->handle();

    Mail::assertSent(\App\Mail\DeanOfAdministrationEmail::class, function (\App\Mail\DeanOfAdministrationEmail $mail) use ($job) {
        if (!$mail->hasTo($job->approverEmail)) {
            return false;
        }

        return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
    });

});

//-----------Test Rejection remail-----------------------------
it('send rejection email', function () {

    Mail::fake();

    $email = 'representative@upr.edu';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $justification = "Mising required documents";
    $job = new SendRejectionEmailJob($email, $eventData,$justification);
    $job->handle();
    Mail::assertSent(RejectionEmail::class, function (RejectionEmail $mail) use ($job) {
        if (!$mail->hasTo($job->creatorEmail)) {
            return false;
        }
        if (!expect($mail->justification)->toBe($job->justification)) {
            return false;
        }
        return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
    });
});

//-------Sanction email---------------

it('send sanction email', function () {
    Mail::fake();
    $email = 'representative@upr.edu';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $job = new SendSanctionedEmailJob($email, $eventData);
    $job->handle();

    Mail::assertSent(SanctionEmail::class, function (SanctionEmail $mail) use ($job) {
        if (!$mail->hasTo($job->creatorEmail)) {
            return false;
        }

        return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
    });
});

//---------------Withdrawal email------------------
it('send withdrawal email', function () {

    Mail::fake();

    $emails = ['advisor@upr.edu','venue_manager@upr.edu','dsca@upr.edu'];
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $justification = "Logistics issues";
    $job = new SendWithdrawalEmailJob($emails, $eventData,$justification);
    $job->handle();

    Mail::assertSent(WithdrawalEmail::class,count($emails));

    foreach ($job->recipientEmails as $recipientEmail) {

        Mail::assertSent(WithdrawalEmail::class, function (WithdrawalEmail $mail) use ($job,$recipientEmail) {
            if (!$mail->hasTo($recipientEmail)) {
                return false;
            }
            if (!expect($mail->justification)->toBe($job->justification)) {
                return false;
            }
            return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
        });




    }

});

//-------Cancelation Email------------------
it('send cancelaton email', function () {

    Mail::fake();

    $emails = ['advisor@upr.edu','venue_manager@upr.edu','dsca@upr.edu'];
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $justification = "Low attendance";
    $job = new SendCancelationEmailJob($emails, $eventData,$justification);
    $job->handle();

    Mail::assertSent(CancelationEmail::class,count($emails));

    foreach ($job->recipientEmails as $recipientEmail) {

        Mail::assertSent(CancelationEmail::class, function (CancelationEmail $mail) use ($job,$recipientEmail) {
            if (!$mail->hasTo($recipientEmail)) {
                return false;
            }
            if (!expect($mail->justification)->toBe($job->justification)) {
                return false;
            }
            return array_all($job->eventData, fn($value, $key) => $mail->eventData[$key] == $value);
        });
    }
});

//----------------------------------------------------------------------------------------------------------------------------------------------------------
//--------------------------------------Asynchronous--------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------------------------------------------
it('is queued on the emails queue with a delay (Advisor Email)', function () {
    Queue::fake();

    $email = 'advisor@upr.edu';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    SendAdvisorEmailJob::dispatch($email, $eventData)->onQueue('emails')->delay(now()->addMinutes(10));

    Queue::assertPushed(SendAdvisorEmailJob::class, function (SendAdvisorEmailJob $job) {
        return $job->queue === 'emails' // if you set it
            && $job->delay !== null;
    });
});

it('is queued on the emails queue with a delay (Approval Required Email)', function () {
    Queue::fake();

    $email = 'approver@upr.edu';
    $approverType = 'venue';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];

    SendApprovalRequiredEmailJob::dispatch($email,$approverType,$eventData)->onQueue('emails')->delay(now()->addMinutes(10));

    Queue::assertPushed(SendApprovalRequiredEmailJob::class, function (SendApprovalRequiredEmailJob $job) {
        return $job->queue === 'emails' // if you set it
            && $job->delay !== null;
    });
});

it('is queued on the emails queue with a delay (Rejection Email)', function () {
    Queue::fake();

    $email = 'representative@upr.edu';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $justification = "Mising required documents";

    SendRejectionEmailJob::dispatch($email,$eventData,$justification)->onQueue('emails')->delay(now()->addMinutes(10));
    Queue::assertPushed(SendRejectionEmailJob::class, function (SendRejectionEmailJob $job) {
        return $job->queue === 'emails' // if you set it
            && $job->delay !== null;
    });

});

it('is queued on the emails queue with a delay (Sanction Email)', function () {
    Queue::fake();
    $email = 'representative@upr.edu';
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    SendSanctionedEmailJob::dispatch($email,$eventData)->onQueue('emails')->delay(now()->addMinutes(10));
    Queue::assertPushed(SendSanctionedEmailJob::class, function (SendSanctionedEmailJob $job) use ($email) {

        return $job->queue === 'emails' // if you set it
            && $job->delay !== null
            && $job->creatorEmail === $email;
    });
});

it('is queued on the emails queue with a delay (Withdrawal Email)', function () {
    Queue::fake();

    $emails = ['advisor@upr.edu','venue_manager@upr.edu','dsca@upr.edu'];
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $justification = "Logistics issues";
    SendWithdrawalEmailJob::dispatch($emails,$eventData, $justification)->onQueue('emails')->delay(now()->addMinutes(10));

    Queue::assertPushed(SendWithdrawalEmailJob::class, function (SendWithdrawalEmailJob $job) use ($emails) {

        return $job->queue === 'emails' // if you set it
            && $job->delay !== null
            && $job->recipientEmails === $emails;
    });
});

it('is queued on the emails queue with a delay (Cancelation Email)', function () {
    Queue::fake();

    $emails = ['advisor@upr.edu','venue_manager@upr.edu','dsca@upr.edu'];
    $eventData = [
        'event_name'=> 'Resume Workshop',
        'event_id'=>42,
    ];
    $justification = "Low attendance";
    SendCancelationEmailJob::dispatch($emails,$eventData, $justification)->onQueue('emails')->delay(now()->addMinutes(10));

    Queue::assertPushed(SendCancelationEmailJob::class, function (SendCancelationEmailJob $job) use ($emails) {

        return $job->queue === 'emails' // if you set it
            && $job->delay !== null
            && $job->recipientEmails === $emails;
    });
});






