<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_server_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('game_server_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('map')->default('TheIsland_WP');

            $table->unsignedInteger('game_port')->default(7777);
            $table->unsignedInteger('query_port')->default(27015);
            $table->unsignedInteger('rcon_port')->default(27020);

            $table->string('session_name')->default('ARK Server');
            $table->string('admin_password')->nullable();
            $table->string('server_password')->nullable();
            $table->unsignedInteger('max_players')->default(70);

            $table->json('extra_args')->nullable();

            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_server_profiles');
    }
};
