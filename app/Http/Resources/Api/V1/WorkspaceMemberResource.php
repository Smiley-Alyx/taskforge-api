<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'workspace_id' => $this->resource->workspace_id,
            'user_id' => $this->resource->user_id,
            'role' => $this->resource->role,
            'joined_at' => $this->resource->joined_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->resource->user)),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
