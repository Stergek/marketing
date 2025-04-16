<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('roas');
            $table->decimal('cpc', 10, 2)->default(0.00)->after('spend');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('cpc');
            $table->decimal('roas', 10, 2)->default(0.00)->after('spend');
        });
    }
};
