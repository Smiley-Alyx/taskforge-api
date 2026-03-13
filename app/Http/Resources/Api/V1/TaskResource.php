<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'workspace_id' => $this->resource->workspace_id,
            'project_id' => $this->resource->project_id,
            'number' => $this->resource->number,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'priority' => $this->resource->priority,
            'assignee_id' => $this->resource->assignee_id,
            'reporter_id' => $this->resource->reporter_id,
            'due_at' => $this->resource->due_at?->toISOString(),
            'position' => $this->resource->position,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
