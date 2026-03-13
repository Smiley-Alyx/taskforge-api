<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'workspace_id' => $this->resource->workspace_id,
            'task_id' => $this->resource->task_id,
            'author_id' => $this->resource->author_id,
            'body' => $this->resource->body,
            'edited_at' => $this->resource->edited_at?->toISOString(),
            'author' => $this->whenLoaded('author', fn () => new UserResource($this->resource->author)),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
