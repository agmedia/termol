<?php

namespace App\Models\Catalog\Product;

use App\Models\Concerns\HasConfiguredMedia;
use App\Models\Catalog\Action\CatalogAction;
use App\Models\Catalog\Attribute\Attribute;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Option\Option;
use App\Models\Content\Support\Comment;
use App\Models\Settings\Local\TaxRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;

class Product extends Model implements HasMedia
{
    use HasConfiguredMedia;

    /** @var array<string, array{count:int,avg:float}> */
    private static array $approvedCommentSummaryCache = [];

    protected $fillable = [
        'code',
        'sku',
        'is_active',
        'manufacturer_id',
        'tax_rate_id',
        'base_price',
        'stock_qty',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'manufacturer_id' => 'int',
        'tax_rate_id' => 'int',
        'base_price' => 'decimal:2',
        'stock_qty' => 'int',
        'payload' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation(string $locale): HasOne
    {
        return $this->hasOne(ProductTranslation::class)->where('locale', $locale);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['sort_order', 'is_primary'])
            ->withTimestamps();
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'catalog_option_product', 'product_id', 'option_id')
            ->withPivot(['sort_order', 'is_required'])
            ->withTimestamps();
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'catalog_attribute_product', 'product_id', 'attribute_id')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function optionValues(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class, 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function directActions(): BelongsToMany
    {
        return $this->belongsToMany(CatalogAction::class, 'catalog_action_targets', 'target_id', 'action_id')
            ->wherePivot('target_type', CatalogAction::TARGET_PRODUCT)
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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopeWithApprovedCommentSummary(Builder $query, array $locales = []): Builder
    {
        $normalizedLocales = collect($locales)
            ->map(fn ($locale): string => trim((string) $locale))
            ->filter(fn (string $locale): bool => $locale !== '')
            ->unique()
            ->values()
            ->all();

        $approvedComments = function (Builder $commentQuery) use ($normalizedLocales): void {
            $commentQuery
                ->whereNull('parent_id')
                ->where('status', Comment::STATUS_APPROVED);

            if ($normalizedLocales !== []) {
                $commentQuery->whereIn('locale', $normalizedLocales);
            }
        };

        $approvedRatings = function (Builder $commentQuery) use ($approvedComments): void {
            $approvedComments($commentQuery);
            $commentQuery->whereNotNull('rating');
        };

        return $query
            ->withCount([
                'comments as approved_comments_count' => $approvedComments,
            ])
            ->withAvg([
                'comments as approved_comments_avg_rating' => $approvedRatings,
            ], 'rating');
    }

    /**
     * @param  array<int, string>  $locales
     * @return array{count:int,avg:float}
     */
    public function approvedCommentSummary(array $locales = []): array
    {
        $attributes = $this->getAttributes();
        if (array_key_exists('approved_comments_count', $attributes) && array_key_exists('approved_comments_avg_rating', $attributes)) {
            return [
                'count' => (int) ($attributes['approved_comments_count'] ?? 0),
                'avg' => round((float) ($attributes['approved_comments_avg_rating'] ?? 0), 2),
            ];
        }

        $normalizedLocales = collect($locales)
            ->map(fn ($locale): string => trim((string) $locale))
            ->filter(fn (string $locale): bool => $locale !== '')
            ->unique()
            ->values()
            ->all();

        $cacheKey = (string) $this->getKey().'|'.implode(',', $normalizedLocales);
        if (! isset(self::$approvedCommentSummaryCache[$cacheKey])) {
            $statsQuery = $this->comments()
                ->whereNull('parent_id')
                ->where('status', Comment::STATUS_APPROVED);

            if ($normalizedLocales !== []) {
                $statsQuery->whereIn('locale', $normalizedLocales);
            }

            $stats = $statsQuery
                ->selectRaw('COUNT(*) as review_count, COALESCE(AVG(rating), 0) as avg_rating')
                ->first();

            self::$approvedCommentSummaryCache[$cacheKey] = [
                'count' => (int) ($stats?->review_count ?? 0),
                'avg' => round((float) ($stats?->avg_rating ?? 0), 2),
            ];
        }

        return self::$approvedCommentSummaryCache[$cacheKey];
    }
}
