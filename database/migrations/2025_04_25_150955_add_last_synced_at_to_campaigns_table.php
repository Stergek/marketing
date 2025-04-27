<?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('campaigns', function (Blueprint $table) {
               $table->timestamp('last_synced_at')->nullable()->after('revenue');
           });
       }

       public function down(): void
       {
           Schema::table('campaigns', function (Blueprint $table) {
               $table->dropColumn('last_synced_at');
           });
       }
   };