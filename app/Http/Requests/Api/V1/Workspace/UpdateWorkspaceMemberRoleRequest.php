<?php

namespace App\Http\Requests\Api\V1\Workspace;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user() !== null && $this->user()->can('update', $workspace);
    }

    public function rules(): array
    {
        /** @var WorkspaceMember $member */
        $member = $this->route('member');

        return [
            'role' => [
                'required',
                'string',
                'in:'.implode(',', [WorkspaceRole::Admin->value, WorkspaceRole::Member->value, WorkspaceRole::Viewer->value]),
            ],
        ];
    }
}
