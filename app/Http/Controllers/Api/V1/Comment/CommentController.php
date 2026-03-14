<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Events\ActivityOccurred;
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

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'comment.created',
            Comment::class,
            (int) $comment->getKey(),
            [
                'task_id' => (int) $task->getKey(),
            ],
            $request->ip(),
            $request->userAgent(),
        );

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

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'comment.updated',
            Comment::class,
            (int) $comment->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return new CommentResource($comment->fresh()->load('author'));
    }

    public function destroy(Request $request, Workspace $workspace, Comment $comment)
    {
        if ((int) $comment->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $comment);

        $comment->delete();

        ActivityOccurred::dispatch(
            (int) $workspace->getKey(),
            (int) $request->user()->getKey(),
            'comment.deleted',
            Comment::class,
            (int) $comment->getKey(),
            null,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(null, 204);
    }
}
