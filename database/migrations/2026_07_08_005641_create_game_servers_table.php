<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');

            $table->string('host');
            $table->unsignedInteger('port')->default(22);
            $table->string('username')->default('ark-sa');
            $table->string('ssh_key_path');

            $table->string('server_dir')->default('/ark-server/server');
            $table->string('backups_dir')->default('/ark-server/backups');
            $table->string('logs_dir')->default('/ark-server/logs');
            $table->string('binary_path')->default('ShooterGame/Binaries/Win64/ArkAscendedServer.exe');
            $table->string('proton_path')->default('/opt/glorious_eggroll/proton/bin/wine64');
            $table->string('steam_app_id')->default('2430930');

            $table->string('map')->default('TheIsland_WP');
            $table->json('game_options')->nullable();
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_servers');
    }
};
