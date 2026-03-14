<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspace\UpdateWorkspaceMemberRoleRequest;
use App\Http\Resources\Api\V1\WorkspaceMemberResource;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $members = $workspace->members()
            ->with('user')
            ->orderBy('id')
            ->paginate(50);

        return WorkspaceMemberResource::collection($members);
    }

    public function update(UpdateWorkspaceMemberRoleRequest $request, Workspace $workspace, WorkspaceMember $member)
    {
        if ((int) $member->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        if ((int) $workspace->owner_id === (int) $member->user_id) {
            abort(422);
        }

        $member->update([
            'role' => $request->string('role')->toString(),
        ]);

        return new WorkspaceMemberResource($member->fresh()->load('user'));
    }
}
