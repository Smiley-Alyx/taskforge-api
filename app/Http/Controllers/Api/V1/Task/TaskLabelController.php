<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\AttachLabelsToTaskRequest;
use App\Http\Resources\Api\V1\LabelResource;
use App\Models\Label;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\Request;

class TaskLabelController extends Controller
{
    public function store(AttachLabelsToTaskRequest $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $labelIds = $request->validated('label_ids');

        $count = Label::query()
            ->where('workspace_id', $workspace->getKey())
            ->whereIn('id', $labelIds)
            ->count();

        if ($count !== count($labelIds)) {
            abort(404);
        }

        $task->labels()->syncWithoutDetaching($labelIds);

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'task.labels_attached',
            Task::class,
            (int) $task->getKey(),
            [
                'label_ids' => array_values($labelIds),
            ],
            $request->ip(),
            $request->userAgent(),
        );

        $labels = $task->labels()->orderBy('name')->get();

        return LabelResource::collection($labels);
    }

    public function destroy(Request $request, Workspace $workspace, Task $task, Label $label)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        if ((int) $label->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $task);

        $task->labels()->detach($label->getKey());

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'task.label_detached',
            Task::class,
            (int) $task->getKey(),
            [
                'label_id' => (int) $label->getKey(),
            ],
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(null, 204);
    }
}
