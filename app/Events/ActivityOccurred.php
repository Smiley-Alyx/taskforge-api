<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityOccurred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $workspaceId,
        public readonly ?int $actorId,
        public readonly string $action,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly ?array $context = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
    ) {
    }
}
