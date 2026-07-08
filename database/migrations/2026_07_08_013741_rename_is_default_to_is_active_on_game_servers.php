<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_servers', function (Blueprint $table) {
            $table->renameColumn('is_default', 'is_active');
        });
    }

    public function down(): void
    {
        Schema::table('game_servers', function (Blueprint $table) {
            $table->renameColumn('is_active', 'is_default');
        });
    }
};
