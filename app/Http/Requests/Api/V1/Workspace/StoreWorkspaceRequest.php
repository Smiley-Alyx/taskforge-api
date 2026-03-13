<?php

namespace App\Http\Requests\Api\V1\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'slug' => ['required', 'string', 'min:2', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:workspaces,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
