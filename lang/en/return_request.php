<?php

return [
    'page_title' => 'Returns and Claims',
    'eyebrow' => 'Customer form',
    'heading' => 'Returns and claims form',
    'subheading' => 'Send order details and the items you would like to return or claim.',
    'sent_status' => 'Thank you. Your return or claim request has been sent successfully.',
    'captcha_failed' => 'Security verification failed. Please try again.',
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute field must be a valid email address.',
        'min_string' => 'The :attribute field must be at least :min characters.',
        'max_string' => 'The :attribute field may not be greater than :max characters.',
        'security_check' => 'security check',
        'inline' => [
            'email_required' => 'Please enter your email address.',
            'email_invalid' => 'Please enter a valid email address.',
            'order_number_required' => 'Please enter the order number.',
            'return_items_required' => 'Please enter the items for return.',
            'return_items_min' => 'Items for return must be at least 2 characters.',
        ],
    ],
    'form' => [
        'email' => 'Customer email',
        'order_number' => 'Order number',
        'return_items' => 'Items for return',
        'return_items_placeholder' => 'For example item name, SKU, size, or quantity',
        'note' => 'Note',
        'note_placeholder' => 'Additional return or claim information',
        'submit' => 'Send request',
    ],
    'mail' => [
        'subject' => '[Return/Claim] Order :order',
        'subject_fallback' => '[Return/Claim] New request',
        'email' => 'Customer email',
        'order_number' => 'Order number',
        'return_items' => 'Items for return',
        'note' => 'Note',
    ],
    'help' => [
        'title' => 'What to enter?',
        'body' => 'List the products you are returning or claiming in the items field. The note can stay empty if there is nothing else to add.',
    ],
];
