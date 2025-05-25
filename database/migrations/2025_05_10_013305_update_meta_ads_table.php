<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMetaAdsTable extends Migration
{
    public function up()
    {
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->dropColumn('impressions');
            $table->text('destination')->nullable()->after('type');
        });
    }

    public function down()
    {
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->integer('impressions')->default(0)->after('media_type');
            $table->dropColumn('destination');
        });
    }
}