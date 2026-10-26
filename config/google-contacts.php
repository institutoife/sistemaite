<?php

return [
    // Set cron on a hosting account that runs gcontact:work --cron periodically.
    'process_mode' => env('GOOGLE_CONTACTS_PROCESS_MODE', 'worker'),
    // This must match the actual interval allowed and configured in cPanel.
    'cron_interval_minutes' => (int) env('GOOGLE_CONTACTS_CRON_INTERVAL', 5),
];
