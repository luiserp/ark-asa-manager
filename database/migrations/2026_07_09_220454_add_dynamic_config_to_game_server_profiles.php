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
            $table->boolean('dynamic_config_enabled')->default(false)->after('mods');
            $table->json('dynamic_config')->nullable()->after('dynamic_config_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('game_server_profiles', function (Blueprint $table) {
            $table->dropColumn(['dynamic_config_enabled', 'dynamic_config']);
        });
    }
};
