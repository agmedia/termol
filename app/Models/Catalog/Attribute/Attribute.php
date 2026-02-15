<?php

namespace App\Models\Catalog\Attribute;

use App\Models\Catalog\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attribute extends Model
{
    public const TYPE_SELECT = 'select';
    public const TYPE_MULTI = 'multi';

    protected $table = 'catalog_attributes';

    protected $fillable = [
        'code',
        'group_code',
        'type',
        'is_active',
        'sort_order',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'sort_order' => 'int',
        'payload' => 'array',
    ];

    /**
     * @return array<int, string>
     */
    public static function availableTypes(): array
    {
        return [
            self::TYPE_SELECT,
            self::TYPE_MULTI,
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class, 'attribute_id');
    }

    public function translation(string $locale): HasOne
    {
        return $this->hasOne(AttributeTranslation::class, 'attribute_id')
            ->where('locale', $locale);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_attribute_product', 'attribute_id', 'product_id')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
