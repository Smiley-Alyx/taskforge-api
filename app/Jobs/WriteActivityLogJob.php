<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WriteActivityLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $workspaceId,
        public readonly ?int $actorId,
        public readonly string $action,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly ?array $context,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
    ) {
    }

    public function handle(): void
    {
        ActivityLog::query()->create([
            'workspace_id' => $this->workspaceId,
            'actor_id' => $this->actorId,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'context' => $this->context,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'created_at' => now(),
        ]);
    }
}
