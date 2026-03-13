<?php

namespace App\Http\Requests\Api\V1\Project;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'key' => ['required', 'string', 'min:2', 'max:16', 'regex:/^[A-Z][A-Z0-9]*$/', 'unique:projects,key,NULL,id,workspace_id,'.$workspace->getKey()],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
