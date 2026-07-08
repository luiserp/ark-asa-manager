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
        Schema::table('game_server_profiles', function (Blueprint $table) {
            $table->boolean('battleye_enabled')->default(true)->after('max_players');
        });
    }

    public function down(): void
    {
        Schema::table('game_server_profiles', function (Blueprint $table) {
            $table->dropColumn('battleye_enabled');
        });
    }
};
