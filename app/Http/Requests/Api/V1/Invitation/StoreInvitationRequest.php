<?php

namespace App\Http\Requests\Api\V1\Invitation;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'role' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, WorkspaceRole::cases()))],
        ];
    }
}
