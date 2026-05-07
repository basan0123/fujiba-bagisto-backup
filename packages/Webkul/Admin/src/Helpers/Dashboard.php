<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Admin\Helpers\Reporting\Customer;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Sale;
use Webkul\Admin\Helpers\Reporting\Visitor;

class Dashboard
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Sale $saleReporting,
        protected Product $productReporting,
        protected Customer $customerReporting,
        protected Visitor $visitorReporting
    ) {}

    /**
     * Returns the overall statistics.
     */
    public function getOverAllStats(): array
    {
        return [
            'total_customers' => $this->customerReporting->getTotalCustomersProgress(),
            'total_orders' => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales' => $this->saleReporting->getTotalSalesProgress(),
            'avg_sales' => $this->saleReporting->getAverageSalesProgress(),
            'total_unpaid_invoices' => [
                'total' => $total = $this->saleReporting->getTotalPendingInvoicesAmount(),
                'formatted_total' => core()->formatBasePrice($total),
            ],
        ];
    }

    /**
     * Returns the today statistics.
     */
    public function getTodayStats(): array
    {
        // Defensive map+filter: skip orders that are null or missing the
        // `status` field. A historical bug where billing_address/payment
        // relations were null caused PHP to either throw or yield malformed
        // rows, which then surfaced in the browser as
        // "Cannot read properties of null (reading 'status')".
        // Filtering at the source means even browsers with stale cached
        // blade/Vue templates can't crash on this anymore.
        $orders = $this->saleReporting->getTodayOrders()
            ->filter(fn ($order) => $order !== null && $order->status !== null)
            ->map(function ($order) {
                try {
                    $items = view('admin::sales.orders.items', compact('order'))->render();
                } catch (\Throwable $e) {
                    $items = '';
                }

                return [
                    'id'                          => $order->id,
                    'increment_id'                => $order->id,
                    'status'                      => $order->status,
                    'status_label'                => $order->status_label ?? $order->status,
                    'payment_method'              => $order->payment?->method
                        ? core()->getConfigData('sales.payment_methods.'.$order->payment->method.'.title')
                        : null,
                    'base_grand_total'            => $order->base_grand_total,
                    'formatted_base_grand_total'  => core()->formatBasePrice($order->base_grand_total),
                    'channel_name'                => $order->channel_name,
                    'customer_email'              => $order->customer_email,
                    'customer_name'               => $order->customer_full_name,
                    'items'                       => $items,
                    'billing_address'             => ($order?->billing_address?->city ?? '').
                        ($order?->billing_address?->country
                            ? ', '.core()->country_name($order->billing_address->country)
                            : ''),
                    'created_at'                  => $order->created_at?->format('d M Y, H:i:s') ?? '',
                ];
            })
            ->filter(fn ($row) => $row !== null && ! empty($row['status']))
            ->values();

        return [
            'total_sales'     => $this->saleReporting->getTodaySalesProgress(),
            'total_orders'    => $this->saleReporting->getTodayOrdersProgress(),
            'total_customers' => $this->customerReporting->getTodayCustomersProgress(),
            'orders'          => $orders,
        ];
    }

    /**
     * Returns the today statistics.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockThresholdProducts()
    {
        $products = $this->productReporting->getStockThresholdProducts(5);

        $products = $products->map(function ($product) {
            return [
                'id' => $product->product_id,
                'sku' => $product->product->sku,
                'name' => $product->product->name,
                'price' => $product->product->price,
                'formatted_price' => core()->formatBasePrice($product->product->price),
                'total_qty' => $product->total_qty,
                'image' => $product->product->base_image_url,
            ];
        });

        return $products;
    }

    /**
     * Returns sales statistics.
     */
    public function getSalesStats(): array
    {
        return [
            'total_orders' => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales' => $this->saleReporting->getTotalSalesProgress(),
            'over_time' => $this->saleReporting->getCurrentTotalSalesOverTime(),
        ];
    }

    /**
     * Returns visitors statistics.
     */
    public function getVisitorStats(): array
    {
        return [
            'total' => $this->visitorReporting->getTotalVisitorsProgress(),
            'unique' => $this->visitorReporting->getTotalUniqueVisitorsProgress(),
            'over_time' => $this->visitorReporting->getCurrentTotalVisitorsOverTime(),
        ];
    }

    /**
     * Returns top selling products statistics.
     */
    public function getTopSellingProducts(): Collection
    {
        return $this->productReporting->getTopSellingProductsByRevenue(5);
    }

    /**
     * Returns top customers statistics.
     */
    public function getTopCustomers(): EloquentCollection
    {
        $customers = $this->customerReporting->getCustomersWithMostSales(5);

        $customers->map(function ($customer) {
            $customer->formatted_total = core()->formatBasePrice($customer->total);
        });

        return $customers;
    }

    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->saleReporting->getStartDate();
    }

    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->saleReporting->getEndDate();
    }

    /**
     * Returns date range
     */
    public function getDateRange(): string
    {
        return $this->getStartDate()->format('d M').' - '.$this->getEndDate()->format('d M');
    }
}
