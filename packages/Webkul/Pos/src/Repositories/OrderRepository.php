<?php

namespace Webkul\Pos\Repositories;

use Illuminate\Container\Container;
use Intervention\Image\Facades\Image;
use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\RefundRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class OrderRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected InvoiceRepository $invoiceRepository,
        protected ShipmentRepository $shipmentRepository,
        protected RefundRepository $refundRepository,
        protected CustomerCreditRepository $customerCreditRepository,
        protected Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Order::class;
    }

    /**
     * Create order for POS
     */
    public function create(array $data)
    {
        [$order, $args] = [$data['order'], $data['args']];

        $customerCredit = $this->customerCreditRepository->create($data);

        $posOrder = $this->model->create($this->prepareOrderData($data));

        $invoiceItems = [];

        $shipmentItems = [];

        $refundItems = [];

        $inventorySourceId = $posOrder->outlet->inventory_source_id;

        foreach ($order->items as $item) {
            $invoiceItems[$item->id] = $item->qty_ordered;

            if ($item->isStockable()) {
                $shipmentItems[$item->id][$inventorySourceId] = $item->qty_ordered;
            }

            if (data_get($args, 'is_returned')) {
                $refundItems[$item->id] = $item->qty_ordered;
            }
        }

        $this->invoiceRepository->create([
            'invoice' => [
                'items'      => $invoiceItems,
                'email_sent' => 1,
            ],
            'order_id' => $order->id,
        ]);

        if (! empty($shipmentItems)) {
            $this->shipmentRepository->create([
                'shipment'  => [
                    'carrier_title' => '',
                    'track_number'  => rand(pow(10, 4), pow(10, 5) - 1),
                    'source'        => $inventorySourceId,
                    'items'         => $shipmentItems,
                    'email_sent'    => 1,
                ],
                'order_id' => $order->id,
            ]);
        }

        if (! empty($refundItems)) {
            $refund = [
                'order_id' => $order->id,
                'refund'   => [
                    'shipping'          => 0,
                    'adjustment_refund' => 0,
                    'adjustment_fee'    => 0,
                    'items'             => $refundItems,
                ],
            ];

            $this->refundRepository->create($refund);
        }

        return [$posOrder, $customerCredit];
    }

    /**
     * Prepare order data
     */
    protected function prepareOrderData(array $data): array
    {
        [$order, $args, $agent] = [$data['order'], $data['args'], $data['user']];

        $data = [
            'idempotency_key'      => $args['idempotency_key'] ?? null,
            'order_id'             => $order->id,
            'order_ref_id'         => 'N/A',
            'outlet_id'            => $agent->outlet_id,
            'user_id'              => $agent->id,
            'order_note'           => $args['order_note'] ?? '',
            // Per-order training flag. Source of truth = explicit arg from the
            // client; the agent-level pos_users.training_mode is only a safety
            // net (a forgetful native build would still tag the row). The web
            // POS contract is: every syncOrder MUST send trainingMode explicitly.
            'is_training'          => array_key_exists('training_mode', $args)
                ? (bool) $args['training_mode']
                : (bool) ($agent->training_mode ?? false),
            'discount_amount'      => round($order->discount_amount, 2),
            'base_discount_amount' => round($order->base_discount_amount, 2),
            // Order-level discount columns: NULL for sales without an
            // order-level discount (don't pollute every cash-sale row
            // with a zero), value + 'unspecified' default reason once
            // the cashier sets one. The picker UI lands later and
            // replaces 'unspecified' with a specific code.
            'order_discount_amount' => ! empty($args['order_discount_amount'])
                ? round((float) $args['order_discount_amount'], 2)
                : null,
            'order_discount_reason' => ! empty($args['order_discount_amount'])
                ? ($args['order_discount_reason'] ?? 'unspecified')
                : null,
            'order_currency'       => $order->order_currency_code,
        ];

        $data['order_barcode_path'] = $this->generateOrderBarcode($order->id);

        if ($args['payment_mode'] == 'pos_cash') {
            $data['cash_total'] = round($order->base_grand_total, 2);
        }

        if ($args['payment_mode'] == 'pos_card') {
            $data['card_total'] = round($order->base_grand_total, 2);
        }

        if ($args['payment_mode'] == 'pos_split') {
            $cashTotal = core()->convertToBasePrice($args['cash_total'], $order->order_currency_code);
            $cardTotal = core()->convertToBasePrice(($order->grand_total - $args['cash_total']), $order->order_currency_code);

            $data = array_merge($data, [
                'cash_total' => round($cashTotal, 2),
                'card_total' => round($cardTotal, 2),
            ]);
        }

        if (
            $args['payment_mode'] == 'pos_card'
            || $args['payment_mode'] == 'pos_split'
        ) {
            $data = array_merge($data, [
                'bank_name'    => $args['bank_name'],
                'card_details' => $args['card_details'],
            ]);
        }

        return $data;
    }

    /**
     * Generate order barcode
     */
    public function generateOrderBarcode(int $id): string
    {
        $barcodeDirectory = storage_path('app/public/order_barcode/');

        if (! file_exists($barcodeDirectory)) {
            mkdir(storage_path('app/public/order_barcode/'), 0777, true);
        }

        require_once __DIR__.'/../barcode.php';

        $filepath = "$barcodeDirectory$id.png";

        $width = core()->getConfigData('pos.settings.barcode.width') ?: 100;

        $height = core()->getConfigData('pos.settings.barcode.height') ?: 50;

        $imageType = core()->getConfigData('pos.configuration.barcode.image_type');

        barcode($filepath, $id, $width, $height, $imageType);

        Image::cache(fn ($image) => $image->make($filepath)->resize(300, 200)->greyscale());

        return "/order_barcode/$id.png";
    }
}
