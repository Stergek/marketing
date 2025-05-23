<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAdvertisersTable extends Migration
{
    public function up()
    {
        Schema::create('user_advertisers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('advertiser_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'advertiser_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_advertisers');
    }
}