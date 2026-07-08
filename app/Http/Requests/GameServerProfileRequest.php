<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GameServerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'map' => ['required', 'string', 'max:255'],
            'game_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'query_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'rcon_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'session_name' => ['required', 'string', 'max:255'],
            'admin_password' => ['nullable', 'string', 'max:255'],
            'server_password' => ['nullable', 'string', 'max:255'],
            'max_players' => ['required', 'integer', 'min:1', 'max:500'],
            'battleye_enabled' => ['sometimes', 'boolean'],
            'extra_args' => ['nullable', 'array'],
        ];
    }
}
