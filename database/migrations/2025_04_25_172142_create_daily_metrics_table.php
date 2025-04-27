<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('ad_account_id');
            $table->date('date');
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('cpc', 10, 2)->nullable();
            $table->decimal('roas', 10, 2)->nullable();
            $table->decimal('cpm', 10, 2)->nullable();
            $table->decimal('ctr', 10, 2)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'date']);
            $table->index(['ad_account_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};