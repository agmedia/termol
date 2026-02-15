<?php

return [
    'flags' => [
        'user_tracking_enabled' => true,
        'user_loyalty_enabled' => true,
    ],
    'loyalty' => [
        'points_per_currency' => 1.0,
        'min_order_total' => 0.0,
        'reversal_mode' => 'zero_out',
    ],
];
