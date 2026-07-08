<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_servers', function (Blueprint $table) {
            $table->dropColumn(['map', 'game_options']);
        });
    }

    public function down(): void
    {
        Schema::table('game_servers', function (Blueprint $table) {
            $table->string('map')->default('TheIsland_WP');
            $table->json('game_options')->nullable();
        });
    }
};
