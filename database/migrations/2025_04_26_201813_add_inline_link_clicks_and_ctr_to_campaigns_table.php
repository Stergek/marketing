<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('inline_link_clicks')->nullable()->after('clicks');
            $table->decimal('inline_link_click_ctr', 10, 2)->nullable()->after('inline_link_clicks');
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['inline_link_clicks', 'inline_link_click_ctr']);
        });
    }
};