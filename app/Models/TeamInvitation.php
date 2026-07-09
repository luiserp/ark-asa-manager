<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $team_id
 * @property string $email
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TeamInvitation extends Model
{
    use HasUlids;

    protected $fillable = [
        'team_id',
        'email',
        'role',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
