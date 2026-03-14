<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspace\StoreWorkspaceRequest;
use App\Http\Requests\Api\V1\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\Api\V1\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $workspaces = Workspace::query()
            ->where('owner_id', $user->getKey())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->getKey()))
            ->orderBy('id')
            ->paginate(20);

        return WorkspaceResource::collection($workspaces);
    }

    public function store(StoreWorkspaceRequest $request)
    {
        $user = $request->user();

        $workspace = Workspace::query()->create([
            'owner_id' => $user->getKey(),
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'description' => $request->string('description')->toString() ?: null,
        ]);

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $user->getKey(),
            'workspace.created',
            Workspace::class,
            (int) $workspace->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return (new WorkspaceResource($workspace))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        return new WorkspaceResource($workspace);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $workspace->update($request->validated());

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'workspace.updated',
            Workspace::class,
            (int) $workspace->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new WorkspaceResource($workspace);
    }

    public function destroy(Request $request, Workspace $workspace)
    {
        $this->authorize('delete', $workspace);

        $workspace->delete();

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'workspace.deleted',
            Workspace::class,
            (int) $workspace->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(null, 204);
    }
}
