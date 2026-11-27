<?php

declare(strict_types=1);

return [
    // Who receives failed-job alerts (spec 11). Empty: alerts only go to the log / Sentry.
    'alert_email' => env('ALERT_EMAIL'),
];
