<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->renameColumn('toleransi', 'toleransi_max');
            $table->string('toleransi_min', 100)->nullable()->after('toleransi_max')->comment('Minimum tolerance specification');
        });
    }

    public function down(): void
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->dropColumn('toleransi_min');
            $table->renameColumn('toleransi_max', 'toleransi');
        });
    }
};