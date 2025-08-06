<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->string('tooling_code', 50)->nullable()->after('yield')->comment('Tooling/Tool code specification');
        });
    }

    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->dropColumn('tooling_code');
        });
    }
};