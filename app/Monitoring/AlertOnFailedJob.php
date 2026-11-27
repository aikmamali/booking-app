<?php

declare(strict_types=1);

namespace App\Monitoring;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Spec 11: staff hear about failed jobs (e.g. an email that could not be sent). Logged as
 * critical (picked up by Sentry) and mailed to ALERT_EMAIL when set.
 */
final class AlertOnFailedJob
{
    public function handle(JobFailed $event): void
    {
        $job = $event->job->resolveName();

        Log::critical('Queued job failed', [
            'job' => $job,
            'connection' => $event->connectionName,
            'error' => $event->exception->getMessage(),
        ]);

        $to = config('monitoring.alert_email');

        if (! is_string($to) || $to === '') {
            return;
        }

        try {
            Mail::raw(
                "Job: {$job}\nError: {$event->exception->getMessage()}\n\nSee failed_jobs (php artisan queue:failed).",
                fn ($message) => $message->to($to)->subject('['.config('app.name').'] Failed job: '.class_basename($job)),
            );
        } catch (Throwable $e) {
            // Never let the alert itself break the worker.
            Log::error('Could not send failed-job alert', ['error' => $e->getMessage()]);
        }
    }
}
