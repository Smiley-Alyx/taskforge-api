<?php

namespace App\Listeners;

use App\Events\ActivityOccurred;
use App\Jobs\WriteActivityLogJob;

class QueueActivityLogWrite
{
    public function handle(ActivityOccurred $event): void
    {
        WriteActivityLogJob::dispatch(
            $event->workspaceId,
            $event->actorId,
            $event->action,
            $event->subjectType,
            $event->subjectId,
            $event->context,
            $event->ip,
            $event->userAgent,
        );
    }
}
