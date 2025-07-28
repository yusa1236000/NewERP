<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_category_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_category_id');
            $table->unsignedBigInteger('tax_code_id');
            $table->boolean('is_default')->default(false);
            $table->integer('sequence')->default(0);
            $table->timestamps();
            
            $table->unique(['tax_category_id', 'tax_code_id'], 'unique_category_tax');
            $table->foreign('tax_category_id')->references('id')->on('tax_categories')->onDelete('cascade');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_category_tax_codes');
    }
};