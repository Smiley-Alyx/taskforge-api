<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\BulkUpdateTasksRequest;
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

        $query = Task::query()
            ->where('project_id', $project->getKey());

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', (int) $request->input('assignee_id'));
        }

        if ($request->filled('due_date') && ! $request->filled('due_from') && ! $request->filled('due_to')) {
            $query->whereDate('due_at', '=', $request->date('due_date')->toDateString());
        }

        if ($request->filled('due_from')) {
            $query->where('due_at', '>=', $request->date('due_from')->toDateTimeString());
        }

        if ($request->filled('due_to')) {
            $query->where('due_at', '<=', $request->date('due_to')->toDateTimeString());
        }

        $sort = $request->string('sort')->toString();
        $allowedSorts = [
            'id',
            'created_at',
            'updated_at',
            'due_at',
            'priority',
            'status',
            'number',
        ];

        if ($sort !== '') {
            foreach (array_filter(explode(',', $sort)) as $field) {
                $direction = 'asc';
                $name = $field;

                if (str_starts_with($field, '-')) {
                    $direction = 'desc';
                    $name = substr($field, 1);
                }

                if (in_array($name, $allowedSorts, true)) {
                    $query->orderBy($name, $direction);
                }
            }
        } else {
            $query->orderByDesc('id');
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $tasks = $query->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Workspace $workspace, Project $project)
    {
        if ((int) $project->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('createTask', $project);

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

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'task.created',
            Task::class,
            (int) $task->getKey(),
            [
                'project_id' => (int) $project->getKey(),
                'number' => (int) $task->number,
            ],
            $request->ip(),
            $request->userAgent(),
        );

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

    public function bulkUpdate(BulkUpdateTasksRequest $request, Workspace $workspace)
    {
        $validated = $request->validated();
        $taskIds = $validated['task_ids'];
        $changes = $validated['changes'];

        $this->authorize('update', $workspace);

        $tasks = Task::query()
            ->where('workspace_id', $workspace->getKey())
            ->whereIn('id', $taskIds)
            ->get();

        $tasksById = $tasks->keyBy('id');

        foreach ($taskIds as $id) {
            if (! $tasksById->has($id)) {
                abort(404);
            }
        }

        DB::transaction(function () use ($tasks, $changes) {
            foreach ($tasks as $task) {
                $task->update($changes);
            }
        });

        if (array_key_exists('status', $changes)) {
            ActivityOccurred::dispatch(
                (int) $workspace->getKey(),
                (int) $request->user()->getKey(),
                'task.status_bulk_changed',
                Task::class,
                null,
                [
                    'task_ids' => $taskIds,
                    'to' => $changes['status'],
                ],
                $request->ip(),
                $request->userAgent(),
            );
        }

        if (array_key_exists('assignee_id', $changes)) {
            ActivityOccurred::dispatch(
                (int) $workspace->getKey(),
                (int) $request->user()->getKey(),
                'task.assignee_bulk_changed',
                Task::class,
                null,
                [
                    'task_ids' => $taskIds,
                    'to' => $changes['assignee_id'],
                ],
                $request->ip(),
                $request->userAgent(),
            );
        }

        return TaskResource::collection($tasks->fresh());
    }

    public function update(UpdateTaskRequest $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $task);

        $beforeStatus = $task->status;
        $beforeAssigneeId = $task->assignee_id;

        $task->update($request->validated());

        if ($beforeStatus !== $task->status) {
            ActivityOccurred::dispatch(
                (int) $workspace->getKey(),
                (int) $request->user()->getKey(),
                'task.status_changed',
                Task::class,
                (int) $task->getKey(),
                [
                    'from' => $beforeStatus,
                    'to' => $task->status,
                ],
                $request->ip(),
                $request->userAgent(),
            );
        }

        if ((int) $beforeAssigneeId !== (int) $task->assignee_id) {
            ActivityOccurred::dispatch(
                (int) $workspace->getKey(),
                (int) $request->user()->getKey(),
                'task.assignee_changed',
                Task::class,
                (int) $task->getKey(),
                [
                    'from' => $beforeAssigneeId,
                    'to' => $task->assignee_id,
                ],
                $request->ip(),
                $request->userAgent(),
            );
        }

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'task.updated',
            Task::class,
            (int) $task->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new TaskResource($task);
    }

    public function destroy(Request $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $task);

        $task->delete();

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'task.deleted',
            Task::class,
            (int) $task->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(null, 204);
    }
}
