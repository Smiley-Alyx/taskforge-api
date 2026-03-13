<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\StoreTaskRequest;
use App\Http\Requests\Api\V1\Task\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('view', $project);

        $tasks = Task::query()
            ->where('project_id', $project->getKey())
            ->orderByDesc('id')
            ->paginate(20);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $project);

        $task = DB::transaction(function () use ($request, $workspace, $project) {
            $nextNumber = (int) (Task::query()
                ->where('project_id', $project->getKey())
                ->max('number') ?? 0) + 1;

            return Task::query()->create([
                'workspace_id' => $workspace->getKey(),
                'project_id' => $project->getKey(),
                'number' => $nextNumber,
                'title' => $request->string('title')->toString(),
                'description' => $request->string('description')->toString() ?: null,
                'status' => $request->string('status')->toString(),
                'priority' => $request->string('priority')->toString(),
                'assignee_id' => $request->integer('assignee_id') ?: null,
                'reporter_id' => $request->user()->getKey(),
                'due_at' => $request->date('due_at')?->toDateTimeString(),
                'position' => $request->integer('position') ?: null,
            ]);
        });

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Request $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(null, 204);
    }
}
