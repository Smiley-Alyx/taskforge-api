<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Project\StoreProjectRequest;
use App\Http\Requests\Api\V1\Project\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $projects = Project::query()
            ->where('workspace_id', $workspace->getKey())
            ->orderBy('id')
            ->paginate(20);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $project = Project::query()->create([
            'workspace_id' => $workspace->getKey(),
            'key' => $request->string('key')->toString(),
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString() ?: null,
            'is_archived' => false,
            'created_by' => $request->user()->getKey(),
        ]);

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'project.created',
            Project::class,
            (int) $project->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('view', $project);

        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $project);

        $project->update($request->validated());

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'project.updated',
            Project::class,
            (int) $project->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new ProjectResource($project);
    }

    public function destroy(Request $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $project);

        $project->delete();

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'project.deleted',
            Project::class,
            (int) $project->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(null, 204);
    }

    public function archive(Request $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $project);

        $project->update(['is_archived' => true]);

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'project.archived',
            Project::class,
            (int) $project->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new ProjectResource($project);
    }

    public function unarchive(Request $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $project);

        $project->update(['is_archived' => false]);

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'project.unarchived',
            Project::class,
            (int) $project->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new ProjectResource($project);
    }
}
