<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetricsToCampaignsTable extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('revenue', 8, 2)->default(0)->after('cpc');
            $table->bigInteger('impressions')->default(0)->after('revenue');
            $table->bigInteger('clicks')->default(0)->after('impressions');
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['revenue', 'impressions', 'clicks']);
        });
    }
}
