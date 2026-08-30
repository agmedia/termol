<?php

namespace App\Models\Integrations\Msan;

use App\Models\Catalog\Category\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MsanCategoryMapping extends Model
{
    public const STATUS_UNMAPPED = 'unmapped';

    public const STATUS_MAPPED = 'mapped';

    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'msan_category_id',
        'local_category_id',
        'status',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'msan_category_id' => 'int',
            'local_category_id' => 'int',
            'updated_by' => 'int',
        ];
    }

    public function msanCategory(): BelongsTo
    {
        return $this->belongsTo(MsanCategory::class, 'msan_category_id');
    }

    public function localCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'local_category_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
