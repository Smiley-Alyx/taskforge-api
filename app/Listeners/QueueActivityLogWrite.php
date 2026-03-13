<?php

namespace App\Listeners;

use App\Events\ActivityOccurred;
use App\Jobs\WriteActivityLogJob;

class QueueActivityLogWrite
{
    public function handle(ActivityOccurred $event): void
    {
        WriteActivityLogJob::dispatch(
            workspaceId: $event->workspaceId,
            actorId: $event->actorId,
            action: $event->action,
            subjectType: $event->subjectType,
            subjectId: $event->subjectId,
            context: $event->context,
            ip: $event->ip,
            userAgent: $event->userAgent,
        );
    }
}
