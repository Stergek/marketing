<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            AND table_name = 'ad_sets'
            AND index_name = 'ad_sets_campaign_id_date_index'
        ")->count > 0;

        if (!$indexExists) {
            Schema::table('ad_sets', function (Blueprint $table) {
                $table->index(['campaign_id', 'date'], 'ad_sets_campaign_id_date_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ad_sets', function (Blueprint $table) {
            $table->dropIndex('ad_sets_campaign_id_date_index');
        });
    }
};