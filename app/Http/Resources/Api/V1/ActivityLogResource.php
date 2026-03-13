<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'workspace_id' => $this->resource->workspace_id,
            'actor_id' => $this->resource->actor_id,
            'action' => $this->resource->action,
            'subject_type' => $this->resource->subject_type,
            'subject_id' => $this->resource->subject_id,
            'context' => $this->resource->context,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
