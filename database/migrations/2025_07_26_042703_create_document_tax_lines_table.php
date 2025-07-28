<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_tax_lines', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['sales_order', 'sales_invoice', 'purchase_order', 'vendor_invoice', 'sales_quotation', 'vendor_quotation']);
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('line_id')->nullable(); // NULL for header level taxes
            $table->unsignedBigInteger('tax_code_id');
            $table->decimal('tax_base_amount', 15, 2);
            $table->decimal('tax_rate', 8, 4);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('base_currency_tax_amount', 15, 2);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->integer('sequence')->default(0);
            $table->boolean('is_compound')->default(false);
            $table->timestamps();
            
            $table->index(['document_type', 'document_id']);
            $table->index(['document_type', 'document_id', 'line_id']);
            $table->index('tax_code_id');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tax_lines');
    }
};