<?php

namespace App\Models;

use Database\Factories\GameServerProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $game_server_id
 * @property string $name
 * @property string $map
 * @property int $game_port
 * @property int $query_port
 * @property int $rcon_port
 * @property string $session_name
 * @property string|null $admin_password
 * @property string|null $server_password
 * @property int $max_players
 * @property array<string, string>|null $extra_args
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GameServerProfile extends Model
{
    /** @use HasFactory<GameServerProfileFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'game_server_id',
        'name',
        'map',
        'game_port',
        'query_port',
        'rcon_port',
        'session_name',
        'admin_password',
        'server_password',
        'max_players',
        'extra_args',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'game_port' => 'integer',
            'query_port' => 'integer',
            'rcon_port' => 'integer',
            'max_players' => 'integer',
            'extra_args' => 'array',
            'is_active' => 'boolean',
            'admin_password' => 'encrypted',
            'server_password' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<GameServer, $this>
     */
    public function gameServer(): BelongsTo
    {
        return $this->belongsTo(GameServer::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
