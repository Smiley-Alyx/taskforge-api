<?php

namespace App\Http\Requests\Api\V1\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class AttachLabelsToTaskRequest extends FormRequest
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
            'label_ids' => ['required', 'array', 'min:1', 'max:100'],
            'label_ids.*' => ['integer', 'distinct', 'exists:labels,id'],
        ];
    }
}
