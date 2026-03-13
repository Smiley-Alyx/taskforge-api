<?php

namespace App\Http\Requests\Api\V1\Label;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class StoreLabelRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:64', 'unique:labels,name,NULL,id,workspace_id,'.$workspace->getKey()],
            'color' => ['required', 'string', 'max:16'],
        ];
    }
}
