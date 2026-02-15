<?php

namespace App\Models\Catalog\Attribute;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeTranslation extends Model
{
    protected $table = 'catalog_attribute_translations';

    protected $fillable = [
        'attribute_id',
        'locale',
        'group_name',
        'name',
        'slug',
        'description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
