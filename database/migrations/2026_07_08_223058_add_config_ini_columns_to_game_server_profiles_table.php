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
            $table->longText('game_user_settings_ini')->nullable()->after('extra_args');
            $table->longText('game_ini')->nullable()->after('game_user_settings_ini');
        });
    }

    public function down(): void
    {
        Schema::table('game_server_profiles', function (Blueprint $table) {
            $table->dropColumn(['game_user_settings_ini', 'game_ini']);
        });
    }
};
