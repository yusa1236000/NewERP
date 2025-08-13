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
        Schema::table('routings', function (Blueprint $table) {
            $table->decimal('yield1_headerpcc', 8, 4)->nullable()
                ->after('tooling_code')
                ->comment('Yield 1 Header PCC value');
            $table->string('size_headerpcc', 100)->nullable()
                ->after('yield1_headerpcc')
                ->comment('Size Header PCC text value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->dropColumn(['yield1_headerpcc', 'size_headerpcc']);
        });
    }
};
