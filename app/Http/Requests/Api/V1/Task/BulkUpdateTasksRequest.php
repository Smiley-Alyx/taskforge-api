<?php

namespace App\Http\Requests\Api\V1\Task;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user() !== null && $this->user()->can('update', $workspace);
    }

    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1', 'max:200'],
            'task_ids.*' => ['integer', 'distinct', 'exists:tasks,id'],
            'changes' => ['required', 'array', 'min:1'],
            'changes.status' => ['sometimes', 'string', 'in:backlog,todo,in_progress,in_review,done,canceled'],
            'changes.priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'changes.assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'changes.due_at' => ['sometimes', 'nullable', 'date'],
            'changes.position' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
