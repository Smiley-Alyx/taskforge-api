<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'workspace_id' => $this->resource->workspace_id,
            'key' => $this->resource->key,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'is_archived' => (bool) $this->resource->is_archived,
            'created_by' => $this->resource->created_by,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
