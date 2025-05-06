<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertiserTables extends Migration
{
    public function up()
    {
        Schema::create('advertisers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('page_id')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('meta_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained()->onDelete('cascade');
            $table->string('ad_id')->unique();
            $table->string('ad_snapshot_url')->nullable();
            $table->text('creative_body')->nullable();
            $table->string('cta')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('active_duration')->nullable();
            $table->string('media_type')->nullable(); // video, image
            $table->integer('impressions')->nullable();
            $table->json('platforms')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meta_ads');
        Schema::dropIfExists('advertisers');
    }
}
