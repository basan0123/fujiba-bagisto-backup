<?php

namespace Fujiba\Reorder\Services;

class ReorderCalculator
{
    /**
     * suggested_qty = ceil(avg_daily_sales × lead_time_days) + safety_stock - current_stock
     * Clamped to 0.
     */
    public function suggest(int $soldInWindow, int $windowDays, int $stock, ?string $channelCode = null): int
    {
        $leadTime    = (int) ($this->cfg('lead_time_days', $channelCode) ?? 7);
        $safetyStock = (int) ($this->cfg('safety_stock', $channelCode) ?? 0);

        $windowDays = max(1, $windowDays);
        $avgDaily   = $soldInWindow / $windowDays;

        $target     = (int) ceil($avgDaily * $leadTime) + $safetyStock;

        return max(0, $target - $stock);
    }

    public function salesWindowDays(?string $channelCode = null): int
    {
        return max(1, (int) ($this->cfg('sales_window_days', $channelCode) ?? 30));
    }

    public function leadTimeDays(?string $channelCode = null): int
    {
        return max(0, (int) ($this->cfg('lead_time_days', $channelCode) ?? 7));
    }

    private function cfg(string $field, ?string $channelCode)
    {
        return core()->getConfigData('sales.inventory.reorder.'.$field, $channelCode);
    }
}
