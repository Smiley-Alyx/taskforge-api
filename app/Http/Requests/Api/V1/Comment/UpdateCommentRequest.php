<?php

namespace App\Http\Requests\Api\V1\Comment;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Comment $comment */
        $comment = $this->route('comment');

        return $this->user() !== null && $this->user()->can('update', $comment);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:20000'],
        ];
    }
}
