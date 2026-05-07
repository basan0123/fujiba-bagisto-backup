<?php

namespace Fujiba\Dashboard\Listeners;

class InventoryValueListener
{
    /**
     * Temporarily a no-op between D2a (repo refactor) and D2d (listener
     * rewrite for Path B). The widget will not render until D2d ships.
     * Dashboard render still works because addTemplate() is never called.
     */
    public function handle($event): void
    {
        // TODO: D2d will rewrite this to render the Vue tag wrapper only.
    }
}
