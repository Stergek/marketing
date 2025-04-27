<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToDailyMetricsTable extends Migration
{
    public function up()
    {
        Schema::table('daily_metrics', function (Blueprint $table) {
            $table->integer('impressions')->nullable()->after('cpc');
            $table->integer('clicks')->nullable()->after('impressions');
            $table->integer('inline_link_clicks')->nullable()->after('clicks');
            $table->decimal('revenue', 10, 2)->nullable()->after('inline_link_clicks');
        });
    }

    public function down()
    {
        Schema::table('daily_metrics', function (Blueprint $table) {
            $table->dropColumn(['impressions', 'clicks', 'inline_link_clicks', 'revenue']);
        });
    }
}