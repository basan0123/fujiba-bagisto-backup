<?php

namespace Fujiba\OrdersAdmin\Services;

/**
 * Request-scoped flag that lets pipeline code suspend the InventoryControl
 * observer for the duration of one reconcile pass.
 *
 * Today this class exists in a minimal form because both observers in
 * Fujiba\InventoryControl (ProductInventoryObserver,
 * ProductOrderedInventoryObserver) `use` it at class-load time. Removing
 * the file without removing those imports causes a fatal "no such file"
 * during cancel/reservation writes (caused a 500 on cancellation of #1556
 * 2026-05-11).
 *
 * Default behaviour: isSuspended() returns false → observers reindex
 * normally. The full OrderUpdateService pipeline will use suspendDuring()
 * when it is rebuilt.
 */
class InventoryReconcileGuard
{
    private bool $suspended = false;

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    public function suspendDuring(callable $cb): mixed
    {
        $previous = $this->suspended;
        $this->suspended = true;
        try {
            return $cb();
        } finally {
            $this->suspended = $previous;
        }
    }
}
