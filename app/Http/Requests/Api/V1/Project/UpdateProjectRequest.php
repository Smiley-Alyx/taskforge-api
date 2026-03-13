<?php

namespace App\Http\Requests\Api\V1\Project;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user() !== null && $this->user()->can('update', $project);
    }

    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        /** @var Project $project */
        $project = $this->route('project');

        return [
            'key' => ['sometimes', 'string', 'min:2', 'max:16', 'regex:/^[A-Z][A-Z0-9]*$/', 'unique:projects,key,'.$project->getKey().',id,workspace_id,'.$workspace->getKey()],
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }
}
