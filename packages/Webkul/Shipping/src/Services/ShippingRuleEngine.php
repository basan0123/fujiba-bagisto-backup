<?php

namespace Webkul\Shipping\Services;

use Webkul\Shipping\DTOs\CartDTO;
use Webkul\Shipping\DTOs\ShippingResultDTO;
use Webkul\Shipping\Handlers\BulkBonusHandler;
use Webkul\Shipping\Handlers\CoolFeeHandler;
use Webkul\Shipping\Handlers\FixedShippingHandler;
use Webkul\Shipping\Handlers\FreeShippingHandler;
use Webkul\Shipping\Handlers\PrefectureShippingHandler;
use Webkul\Shipping\Handlers\WeightShippingHandler;

class ShippingRuleEngine
{
    private array $handlers;

    public function __construct(
        private BulkBonusHandler         $bulkBonus,
        private CoolFeeHandler            $coolFee,
        private FreeShippingHandler       $freeShipping,
        private WeightShippingHandler     $weightShipping,
        private PrefectureShippingHandler $prefectureShipping,
        private FixedShippingHandler      $fixedShipping,
    ) {
        $this->handlers = collect([
            $this->bulkBonus,           // 10 — adds free items first
            $this->coolFee,             // 20 — adds surcharge
            $this->freeShipping,        // 30 — may zero base + cool
            $this->weightShipping,      // 35 — weight-based rate
            $this->prefectureShipping,  // 40 — qty + prefecture rate
            $this->fixedShipping,       // 50 — fallback flat fee
        ])->sortBy(fn($h) => $h->getPriority())->values()->all();
    }

    public function calculate(CartDTO $cart): ShippingResultDTO
    {
        $ctx = new ShippingContext();

        foreach ($this->handlers as $handler) {
            if ($handler->supports($cart)) {
                $handler->handle($cart, $ctx);
            }
        }

        return new ShippingResultDTO(
            baseShipping:        $ctx->baseShipping,
            coolFee:             $ctx->coolFee,
            frozenFee:           $ctx->frozenFee,
            total:               $ctx->baseShipping + $ctx->coolFee + $ctx->frozenFee,
            freeShippingApplied: $ctx->freeShippingApplied,
            freeShippingReason:  $ctx->freeShippingReason,
            bonusItems:          $ctx->bonusItems,
            appliedRules:        $ctx->appliedRules,
            breakdown:           $ctx->breakdown,
        );
    }
}
