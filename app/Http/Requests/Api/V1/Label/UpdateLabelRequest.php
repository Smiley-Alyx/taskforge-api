<?php

namespace App\Http\Requests\Api\V1\Label;

use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Label $label */
        $label = $this->route('label');

        return $this->user() !== null && $this->user()->can('update', $label);
    }

    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        /** @var Label $label */
        $label = $this->route('label');

        return [
            'name' => ['sometimes', 'string', 'min:1', 'max:64', 'unique:labels,name,'.$label->getKey().',id,workspace_id,'.$workspace->getKey()],
            'color' => ['sometimes', 'string', 'max:16'],
        ];
    }
}
