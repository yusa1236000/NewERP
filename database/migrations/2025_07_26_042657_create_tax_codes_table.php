<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('tax_code', 20)->unique();
            $table->string('tax_name', 100);
            $table->decimal('tax_rate', 8, 4); // Support up to 9999.9999%
            $table->enum('tax_type', ['vat', 'gst', 'sales_tax', 'service_tax', 'withholding_tax', 'excise_tax']);
            $table->enum('calculation_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->enum('scope', ['sale', 'purchase', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_compound')->default(false);
            $table->boolean('include_base_amount')->default(true);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('tax_code');
            $table->index(['tax_type', 'scope']);
            $table->index(['is_active', 'effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};