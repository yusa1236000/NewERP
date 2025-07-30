<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyToFixedAssetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('FixedAsset', function (Blueprint $table) {
            $table->string('currency', 3)->nullable()->after('status')->comment('Currency code for the fixed asset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('FixedAsset', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
}
