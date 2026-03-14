<?php

namespace App\Http\Controllers\Api\V1\Activity;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function globalIndex(Request $request)
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'action' => ['sometimes', 'string'],
            'actor_id' => ['sometimes', 'integer'],
        ]);

        /** @var Workspace $workspace */
        $workspace = Workspace::query()->findOrFail((int) $validated['workspace_id']);

        return $this->index($request, $workspace);
    }

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $query = ActivityLog::query()->where('workspace_id', $workspace->getKey());

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', (int) $request->input('actor_id'));
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        return ActivityLogResource::collection($logs);
    }
}
