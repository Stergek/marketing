<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAdSetsAndAdsTables extends Migration
{
    public function up()
    {
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_sets');
    }

    public function down()
    {
        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->string('ad_set_id');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('cpc', 10, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->date('date');
            $table->timestamps();
            $table->unique(['ad_set_id', 'date']);
        });

        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('ad_id');
            $table->foreignId('ad_set_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('status');
            $table->string('ad_image')->nullable();
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('cpc', 10, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->date('date');
            $table->timestamps();
            $table->unique(['ad_id', 'date']);
        });
    }
}