<?php

namespace Webkul\Pos\Payment;

use Webkul\Payment\Payment\Payment;

class PosSplit extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code = 'pos_split';

    /**
     * Returns the redirect URL
     *
     * @return string
     */
    public function getRedirectUrl() {}

    /**
     * Returns payment method additional information
     *
     * @return array
     */
    public function getAdditionalDetails()
    {
        return [
            'title' => trans('pos::app.outlet.payment.title'),
            'value' => trans('pos::app.outlet.payment.options.split.title'),
        ];
    }
}
