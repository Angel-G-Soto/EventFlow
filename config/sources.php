<?php

/**
 * This config file exposes an array of whitelisted "source_id" values
 * that are permitted to initiate import requests.
 *
 * Source list is read from ALLOWED_IMPORT_SOURCES in .env (comma-separated).
 * We normalize the list by trimming whitespace and removing blanks.
 */

$raw = env('ALLOWED_IMPORT_SOURCES', '');

$allowed = array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== '');

return [
    'allowed_sources' => $allowed,
];

