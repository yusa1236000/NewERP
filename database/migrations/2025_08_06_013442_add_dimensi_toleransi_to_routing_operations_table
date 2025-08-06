<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->string('dimensi', 100)->nullable()->after('models')->comment('Dimension specification');
            $table->string('toleransi', 100)->nullable()->after('dimensi')->comment('Tolerance specification');
        });
    }

    public function down(): void
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->dropColumn(['dimensi', 'toleransi']);
        });
    }
};