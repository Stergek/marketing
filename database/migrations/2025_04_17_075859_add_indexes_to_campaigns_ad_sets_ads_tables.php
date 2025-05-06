<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIndexesToCampaignsAdSetsAdsTables extends Migration
{
    private function indexExists($table, $column)
    {
        $indexes = DB::select("SHOW INDEXES FROM `{$table}` WHERE Column_name = ?", [$column]);
        return !empty($indexes);
    }

    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!$this->indexExists('campaigns', 'date')) {
                $table->index('date', 'idx_campaigns_date');
            }

            if (!$this->indexExists('campaigns', 'campaign_id')) {
                $table->index('campaign_id', 'idx_campaigns_campaign_id');
            }
        });

        Schema::table('ad_sets', function (Blueprint $table) {
            if (!$this->indexExists('ad_sets', 'date')) {
                $table->index('date', 'idx_ad_sets_date');
            }

            if (!$this->indexExists('ad_sets', 'ad_set_id')) {
                $table->index('ad_set_id', 'idx_ad_sets_ad_set_id');
            }
        });

        Schema::table('ads', function (Blueprint $table) {
            if (!$this->indexExists('ads', 'date')) {
                $table->index('date', 'idx_ads_date');
            }

            if (!$this->indexExists('ads', 'ad_id')) {
                $table->index('ad_id', 'idx_ads_ad_id');
            }
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if ($this->indexExists('campaigns', 'date')) {
                $table->dropIndex('idx_campaigns_date');
            }
            if ($this->indexExists('campaigns', 'campaign_id')) {
                $table->dropIndex('idx_campaigns_campaign_id');
            }
        });

        Schema::table('ad_sets', function (Blueprint $table) {
            if ($this->indexExists('ad_sets', 'date')) {
                $table->dropIndex('idx_ad_sets_date');
            }
            if ($this->indexExists('ad_sets', 'ad_set_id')) {
                $table->dropIndex('idx_ad_sets_ad_set_id');
            }
        });

        Schema::table('ads', function (Blueprint $table) {
            if ($this->indexExists('ads', 'date')) {
                $table->dropIndex('idx_ads_date');
            }
            if ($this->indexExists('ads', 'ad_id')) {
                $table->dropIndex('idx_ads_ad_id');
            }
        });
    }
}