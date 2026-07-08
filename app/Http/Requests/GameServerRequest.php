<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GameServerRequest extends FormRequest
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
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'ssh_key' => [$this->isMethod('POST') ? 'required' : 'nullable', 'file', 'max:10'],
            'server_dir' => ['required', 'string', 'max:500'],
            'backups_dir' => ['required', 'string', 'max:500'],
            'logs_dir' => ['required', 'string', 'max:500'],
            'binary_path' => ['required', 'string', 'max:500'],
            'proton_path' => ['required', 'string', 'max:500'],
            'steam_app_id' => ['required', 'string', 'max:20'],
        ];
    }
}
