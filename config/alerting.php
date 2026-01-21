<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ALERTING_ENABLED', true),

    'recipients' => array_filter([
        env('ALERT_EMAIL_1', 'admin@agency.gov.ph'),
        env('ALERT_EMAIL_2'),
        env('ALERT_EMAIL_3'),
    ]),

    'thresholds' => [
        'failed_jobs' => env('ALERT_FAILED_JOBS_THRESHOLD', 10),
        'slow_queries' => env('ALERT_SLOW_QUERIES_THRESHOLD', 5),
        'disk_usage_percent' => env('ALERT_DISK_USAGE_THRESHOLD', 90),
        'backup_hours' => env('ALERT_BACKUP_HOURS_THRESHOLD', 24),
        'queue_stuck_hours' => env('ALERT_QUEUE_STUCK_THRESHOLD', 2),
        'import_error_rate' => env('ALERT_IMPORT_ERROR_RATE', 0.2),
    ],

    'channels' => [
        'email' => true,
        'slack' => env('ALERT_SLACK_ENABLED', false),
        'sms' => env('ALERT_SMS_ENABLED', false),
    ],

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('SLACK_CHANNEL', '#pds-alerts'),
    ],
];
