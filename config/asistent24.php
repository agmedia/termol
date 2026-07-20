<?php

return [
    'enabled' => (bool) env('ASISTENT24_CONNECTOR_ENABLED', true),
    'store_key' => (string) env('ASISTENT24_STORE_KEY', ''),
    'sync_secret' => (string) env('ASISTENT24_SYNC_SECRET', ''),
    'allowed_skew_seconds' => (int) env('ASISTENT24_ALLOWED_SKEW_SECONDS', 300),
    'include_inactive' => (bool) env('ASISTENT24_EXPORT_INCLUDE_INACTIVE', false),
    'default_locale' => (string) env('ASISTENT24_EXPORT_LOCALE', ''),
];

