<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('name');
            $table->decimal('spend', 10, 2)->default(0.00);
            $table->decimal('roas', 10, 2)->default(0.00);
            $table->date('date')->index();
            $table->unique(['campaign_id', 'date']); // Composite unique key
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
