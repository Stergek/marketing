<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToMetaAdsTable extends Migration
{
    public function up()
    {
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->string('type')->nullable()->after('active_duration');
        });
    }

    public function down()
    {
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}