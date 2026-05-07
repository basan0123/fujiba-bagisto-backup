<?php

namespace Fujiba\Dashboard\Listeners;

use Webkul\Theme\ViewRenderEventManager;

/**
 * Closes the wrapper opened by TodaysGridOpenListener.
 */
class TodaysGridCloseListener
{
    public function handle(ViewRenderEventManager $event): void
    {
        $event->addTemplate('</div>');
    }
}
