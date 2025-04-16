<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAdsTableAdImageColumn extends Migration
{
    public function up()
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->text('ad_image')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('ad_image', 255)->nullable()->change();
        });
    }
}
