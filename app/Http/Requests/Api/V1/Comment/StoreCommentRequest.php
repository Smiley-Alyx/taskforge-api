<?php

namespace App\Http\Requests\Api\V1\Comment;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:1', 'max:20000'],
        ];
    }
}
