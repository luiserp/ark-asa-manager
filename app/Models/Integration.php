<?php

namespace App\Models;

use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $provider
 * @property string|null $label
 * @property string $api_key
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'provider',
        'label',
        'api_key',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }
}
