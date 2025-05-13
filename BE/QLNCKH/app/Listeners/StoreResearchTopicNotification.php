<?php

namespace App\Listeners;

use App\Events\ResearchTopicSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StoreResearchTopicNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ResearchTopicSubmitted $event): void
    {
        //
    }
}
