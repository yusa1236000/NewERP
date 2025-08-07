<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->decimal('yield1', 8, 4)->nullable()->after('overhead_cost')->comment('Yield 1 value for operation');
        });
    }

    public function down()
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->dropColumn('yield1');
        });
    }
};