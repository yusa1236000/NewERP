<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_tax_category_id')->nullable()->after('status');
            $table->unsignedBigInteger('purchase_tax_category_id')->nullable()->after('sale_tax_category_id');
            $table->boolean('is_tax_exempt')->default(false)->after('purchase_tax_category_id');
            $table->string('tax_exempt_reason', 255)->nullable()->after('is_tax_exempt');
            
            $table->index('sale_tax_category_id');
            $table->index('purchase_tax_category_id');
            $table->foreign('sale_tax_category_id')->references('id')->on('tax_categories');
            $table->foreign('purchase_tax_category_id')->references('id')->on('tax_categories');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['sale_tax_category_id']);
            $table->dropForeign(['purchase_tax_category_id']);
            $table->dropIndex(['sale_tax_category_id']);
            $table->dropIndex(['purchase_tax_category_id']);
            $table->dropColumn(['sale_tax_category_id', 'purchase_tax_category_id', 'is_tax_exempt', 'tax_exempt_reason']);
        });
    }
};