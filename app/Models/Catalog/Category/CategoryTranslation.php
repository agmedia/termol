<?php

namespace App\Models\Catalog\Category;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryTranslation extends Model
{
    protected $fillable = [
        'category_id',
        'scope',
        'locale',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
