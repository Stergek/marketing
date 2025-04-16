<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_set_id')->constrained()->onDelete('cascade');
            $table->string('ad_id')->unique(); // Meta Ad ID
            $table->string('name');
            $table->string('ad_image')->nullable(); // URL from creative
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('cpc', 10, 2)->default(0);
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
