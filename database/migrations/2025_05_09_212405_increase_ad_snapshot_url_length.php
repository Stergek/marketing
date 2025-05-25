<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreaseAdSnapshotUrlLength extends Migration
{
    public function up()
    {
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->text('ad_snapshot_url')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('meta_ads', function (Blueprint $table) {
            $table->string('ad_snapshot_url', 255)->nullable()->change();
        });
    }
}