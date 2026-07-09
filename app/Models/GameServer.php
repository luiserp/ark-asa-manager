<?php

namespace App\Models;

use Database\Factories\GameServerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $team_id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $username
 * @property string $ssh_key_path
 * @property string $server_dir
 * @property string $backups_dir
 * @property string $logs_dir
 * @property string $binary_path
 * @property string $proton_path
 * @property string $steam_app_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GameServer extends Model
{
    /** @use HasFactory<GameServerFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::created(function (GameServer $server) {
            $defaults = config('ark.profile_defaults', []);

            $server->profiles()->create([
                'name' => 'Default',
                'map' => $defaults['map'] ?? 'TheIsland_WP',
                'game_port' => $defaults['game_port'] ?? 7777,
                'query_port' => $defaults['query_port'] ?? 27015,
                'rcon_port' => $defaults['rcon_port'] ?? 27020,
                'session_name' => $defaults['session_name'] ?? 'ARK Server',
                'admin_password' => $defaults['admin_password'] ?? null,
                'server_password' => $defaults['server_password'] ?? null,
                'max_players' => $defaults['max_players'] ?? 70,
                'is_active' => true,
            ]);
        });
    }

    protected $fillable = [
        'team_id',
        'name',
        'host',
        'port',
        'username',
        'ssh_key_path',
        'server_dir',
        'backups_dir',
        'logs_dir',
        'binary_path',
        'proton_path',
        'steam_app_id',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<GameServerProfile, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(GameServerProfile::class);
    }
}
