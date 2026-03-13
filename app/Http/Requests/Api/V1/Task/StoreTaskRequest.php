<?php

namespace App\Http\Requests\Api\V1\Task;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user() !== null && $this->user()->can('update', $workspace);
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'string', 'in:backlog,todo,in_progress,in_review,done,canceled'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'position' => ['nullable', 'integer'],
        ];
    }
}
