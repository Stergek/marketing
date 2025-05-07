<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertiserAdCountsTable extends Migration
{
    public function up()
    {
        Schema::create('advertiser_ad_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('active_ad_count');
            $table->timestamps();

            $table->unique(['advertiser_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('advertiser_ad_counts');
    }
}