<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $current_team_id
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('role', 'current_server_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function currentServer(): ?GameServer
    {
        if (! $this->current_team_id) {
            return null;
        }

        $serverId = DB::table('team_user')
            ->where('team_id', $this->current_team_id)
            ->where('user_id', $this->id)
            ->value('current_server_id');

        if (! $serverId) {
            return null;
        }

        $server = GameServer::find($serverId);

        if (! $server || $server->team_id !== $this->current_team_id) {
            DB::table('team_user')
                ->where('team_id', $this->current_team_id)
                ->where('user_id', $this->id)
                ->update(['current_server_id' => null]);

            return null;
        }

        return $server;
    }

    public function switchTeam(Team $team): void
    {
        $this->forceFill(['current_team_id' => $team->id])->save();
    }

    public function switchServer(GameServer $server): void
    {
        DB::table('team_user')
            ->where('team_id', $this->current_team_id)
            ->where('user_id', $this->id)
            ->update(['current_server_id' => $server->id]);
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Create a personal team for this user and set it as current.
     */
    public function createPersonalTeam(): Team
    {
        $team = $this->ownedTeams()->create([
            'name' => $this->name."'s Team",
            'personal_team' => true,
        ]);

        $team->members()->attach($this->id, ['role' => 'owner']);
        $this->switchTeam($team);

        return $team;
    }
}
