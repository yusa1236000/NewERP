<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('moq', 15, 4)->nullable()->after('tape_mat_pcc')->comment('Minimum Order Quantity');
            $table->integer('lead_time')->nullable()->after('moq')->comment('Lead time in days');
            $table->string('item_code_2', 100)->nullable()->after('lead_time')->comment('Alternative item code');
            $table->string('creditor', 255)->nullable()->after('item_code_2');
            $table->string('debtor', 255)->nullable()->after('creditor');
            $table->string('payment_term', 100)->nullable()->after('debtor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'moq',
                'lead_time',
                'item_code_2',
                'creditor',
                'debtor',
                'payment_term'
            ]);
        });
    }
};