<?php

namespace App\Http\Requests\Api\V1\Workspace;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user() !== null && $this->user()->can('update', $workspace);
    }

    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'slug' => ['sometimes', 'string', 'min:2', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:workspaces,slug,'.$workspace->getKey()],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
