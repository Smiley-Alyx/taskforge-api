<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Requests\Api\V1\Comment\UpdateCommentRequest;
use App\Http\Resources\Api\V1\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('view', $task);

        $comments = $task->comments()
            ->with('author')
            ->orderBy('id')
            ->paginate(50);

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Workspace $workspace, Task $task)
    {
        if ((int) $task->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $task);

        $comment = Comment::query()->create([
            'workspace_id' => $workspace->getKey(),
            'task_id' => $task->getKey(),
            'author_id' => $request->user()->getKey(),
            'body' => $request->string('body')->toString(),
        ]);

        return (new CommentResource($comment->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCommentRequest $request, Workspace $workspace, Comment $comment)
    {
        if ((int) $comment->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $comment);

        $comment->update([
            'body' => $request->string('body')->toString(),
            'edited_at' => now(),
        ]);

        return new CommentResource($comment->fresh()->load('author'));
    }

    public function destroy(Request $request, Workspace $workspace, Comment $comment)
    {
        if ((int) $comment->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(null, 204);
    }
}
