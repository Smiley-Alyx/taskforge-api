<?php

namespace App\Http\Requests\Api\V1\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return $this->user() !== null && $this->user()->can('update', $task);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'string', 'in:backlog,todo,in_progress,in_review,done,canceled'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'position' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
