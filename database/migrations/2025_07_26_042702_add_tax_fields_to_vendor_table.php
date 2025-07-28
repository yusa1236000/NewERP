<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('tax_registration_number', 50)->nullable()->after('status');
            $table->string('tax_jurisdiction', 100)->nullable()->after('tax_registration_number');
            $table->unsignedBigInteger('default_tax_category_id')->nullable()->after('tax_jurisdiction');
            $table->boolean('is_tax_exempt')->default(false)->after('default_tax_category_id');
            $table->string('tax_exempt_certificate', 100)->nullable()->after('is_tax_exempt');
            $table->string('tax_exempt_reason', 255)->nullable()->after('tax_exempt_certificate');
            $table->boolean('withholding_tax_applicable')->default(false)->after('tax_exempt_reason');
            $table->decimal('withholding_tax_rate', 5, 2)->nullable()->after('withholding_tax_applicable');
            
            $table->index('tax_registration_number');
            $table->index('default_tax_category_id');
            $table->foreign('default_tax_category_id')->references('id')->on('tax_categories');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['default_tax_category_id']);
            $table->dropIndex(['tax_registration_number']);
            $table->dropIndex(['default_tax_category_id']);
            $table->dropColumn([
                'tax_registration_number', 'tax_jurisdiction', 'default_tax_category_id',
                'is_tax_exempt', 'tax_exempt_certificate', 'tax_exempt_reason',
                'withholding_tax_applicable', 'withholding_tax_rate'
            ]);
        });
    }
};