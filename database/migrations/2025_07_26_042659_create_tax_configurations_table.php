<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 100);
            $table->string('tax_jurisdiction', 100);
            $table->string('tax_authority', 100);
            $table->string('tax_registration_number', 50)->nullable();
            $table->unsignedBigInteger('default_sale_tax_category_id')->nullable();
            $table->unsignedBigInteger('default_purchase_tax_category_id')->nullable();
            $table->boolean('tax_inclusive_pricing')->default(false);
            $table->enum('rounding_method', ['round', 'round_up', 'round_down'])->default('round');
            $table->integer('rounding_precision')->default(2);
            $table->enum('compound_tax_calculation', ['sequential', 'parallel'])->default('sequential');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('default_sale_tax_category_id')->references('id')->on('tax_categories');
            $table->foreign('default_purchase_tax_category_id')->references('id')->on('tax_categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
    }
};