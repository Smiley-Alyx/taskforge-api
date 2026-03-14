<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'workspace_id' => $this->resource->workspace_id,
            'email' => $this->resource->email,
            'role' => $this->resource->role,
            'token' => $this->resource->token,
            'invited_by' => $this->resource->invited_by,
            'accepted_at' => $this->resource->accepted_at?->toISOString(),
            'declined_at' => $this->resource->declined_at?->toISOString(),
            'expires_at' => $this->resource->expires_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
