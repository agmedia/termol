<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Block Types
    |--------------------------------------------------------------------------
    |
    | These are reusable block render-types. Frontend rendering can map each
    | type to its own blade partial.
    |
    */
    'types' => [
        'hero_single' => 'Hero Single Banner',
        'hero_slider' => 'Hero Slider (multi banner)',
        'products_carousel' => 'Products Carousel',
        'blog_grid_3' => 'Blog Grid (3)',
        'cards_2' => 'Cards (2 Col)',
        'hero_main' => 'Hero Main',
        'split_message' => 'Split Message (2 Col)',
        'cards_3' => 'Cards (3 Col)',
        'rich_text' => 'Rich Text',
        'cta_banner' => 'CTA Banner',
        'dev_polishing' => 'Dev Polishing (RTE)',
        'custom' => 'Custom',
    ],

    /*
    |--------------------------------------------------------------------------
    | Placement Registry
    |--------------------------------------------------------------------------
    |
    | Global placements can be used directly.
    | Targeted placements can be combined with:
    | - target_type (category, blog_post, product, page, ...)
    | - target_ref  (slug or id)
    |
    */
    'placements' => [
        'home.hero' => 'Home Hero',
        'home.before_products' => 'Home Before Products',
        'home.after_products' => 'Home After Products',
        'home.bottom' => 'Home Bottom',
        'category.top' => 'Category Top',
        'category.bottom' => 'Category Bottom',
        'product.top' => 'Product Top',
        'product.bottom' => 'Product Bottom',
        'blog.top' => 'Blog Top',
        'blog.bottom' => 'Blog Bottom',
        'page.top' => 'Page Top',
        'page.bottom' => 'Page Bottom',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl_seconds' => 3600,
        'version_key' => 'content_blocks:version',
    ],
];
