<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_product_option_values', function (Blueprint $table): void {
            $table->index(
                ['option_value_id', 'is_active', 'product_id'],
                'catalog_product_option_value_filter_idx'
            );
            $table->index(
                ['parent_option_value_id', 'is_active', 'product_id'],
                'catalog_product_parent_option_filter_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('catalog_product_option_values', function (Blueprint $table): void {
            $table->dropIndex('catalog_product_option_value_filter_idx');
            $table->dropIndex('catalog_product_parent_option_filter_idx');
        });
    }
};
