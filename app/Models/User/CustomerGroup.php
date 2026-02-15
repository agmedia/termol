<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerGroup extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'is_default',
        'sort_order',
        'payload',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_default' => 'bool',
        'sort_order' => 'int',
        'payload' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'customer_group_user')
            ->withTimestamps();
    }
}

