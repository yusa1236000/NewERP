<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->decimal('yield_perikat', 8, 4)->nullable()->after('yield')->comment('Yield perikat percentage (0-100)');
        });
    }

    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->dropColumn('yield_perikat');
        });
    }
};