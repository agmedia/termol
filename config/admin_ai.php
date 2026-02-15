<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin AI Tool Registry
    |--------------------------------------------------------------------------
    |
    | Keep all executable admin-agent tools behind this allowlist.
    | Future domain tools can be added here and then implemented in
    | App\Services\AdminAi\AdminAgentService.
    |
    */
    'tools' => [
        'ensure_category_path' => true,
        'upsert_category_translation' => true,
        'attach_products_by_filter' => true,
        'set_category_state' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Unsupported Request Fallback
    |--------------------------------------------------------------------------
    */
    'fallback' => [
        'notice' => 'If this action is not possible, contact developers for estimate on delivery time and cost.',
        'contact' => env('ADMIN_AI_DEV_CONTACT', 'dev@agshop.local'),
    ],
];
