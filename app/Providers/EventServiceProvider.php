<?php

namespace App\Providers;

use App\Events\ActivityOccurred;
use App\Listeners\QueueActivityLogWrite;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ActivityOccurred::class => [
            QueueActivityLogWrite::class,
        ],
    ];
}
