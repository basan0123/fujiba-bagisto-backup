<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;

class CartAddressRequest extends FormRequest
{
    /**
     * Rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Determine if the product is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->has('billing')) {
            $this->mergeAddressRules('billing');
        }

        if (! $this->input('billing.use_for_shipping')) {
            $this->mergeAddressRules('shipping');
        }

        return $this->rules;
    }

    /**
     * Merge new address rules.
     *
     * @return void
     */
    private function mergeAddressRules(string $addressType)
    {
        $channelRelaxes = $this->channelRelaxesAddressFields();

        $postcodeRules = $channelRelaxes ? ['nullable', new PostCode] : ['required', new PostCode];
        $phoneRules    = $channelRelaxes ? ['nullable', new PhoneNumber] : ['required', new PhoneNumber];

        $this->mergeWithRules([
            "{$addressType}.company_name" => ['nullable'],
            "{$addressType}.first_name" => ['required'],
            "{$addressType}.last_name" => ['required'],
            "{$addressType}.email" => ['required'],
            "{$addressType}.address" => ['required', 'array', 'min:1'],
            "{$addressType}.city" => ['required'],
            "{$addressType}.country" => ['required'],
            "{$addressType}.state" => ['required'],
            "{$addressType}.postcode" => $postcodeRules,
            "{$addressType}.phone" => $phoneRules,
        ]);
    }

    /**
     * mmfood/kimono storefronts accept Messenger-onboarded customers whose
     * profiles lack a real postcode/phone, so admin order create/edit on
     * those channels treats both fields as optional.
     */
    private function channelRelaxesAddressFields(): bool
    {
        $cartId = $this->route('id');
        if (! $cartId) {
            return false;
        }

        $cart = app(CartRepository::class)->find($cartId);
        if (! $cart) {
            return false;
        }

        return in_array(
            optional($cart->channel)->code,
            ['mmfood', 'kimono'],
            true
        );
    }

    /**
     * Merge additional rules.
     */
    private function mergeWithRules($rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }
}
