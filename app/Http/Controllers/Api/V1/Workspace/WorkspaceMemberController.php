<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkspaceMemberResource;
use App\Models\Workspace;
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
}
