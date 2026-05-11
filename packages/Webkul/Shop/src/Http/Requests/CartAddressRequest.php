<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;
use Webkul\Customer\Rules\VatIdRule;

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
     */
    private function mergeAddressRules(string $addressType): void
    {
        // Messenger-onboarded customers (chatpion_subscribe_id present) lack a
        // real email/postcode/phone regardless of which channel they shop on —
        // on Fujiba the kimono channel is coded `default`, so a channel-name
        // gate misses them. Drive the relax off the customer flag instead, and
        // keep the legacy channel list as a fallback for guest checkout on
        // mmfood-style storefronts.
        $messengerCustomer = $this->customerIsMessengerOnboarded();
        $channelRelaxes = $messengerCustomer || $this->channelRelaxesAddressFields();

        $emailRules = $messengerCustomer
            ? ['nullable', 'email']
            : ['required', 'email'];

        $postcodeRules = $channelRelaxes
            ? ['nullable', new PostCode]
            : (core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode]);

        $phoneRules = $channelRelaxes
            ? ['nullable', new PhoneNumber]
            : ['required', new PhoneNumber];

        $this->mergeWithRules([
            "{$addressType}.company_name" => ['nullable'],
            "{$addressType}.first_name" => ['required'],
            "{$addressType}.last_name" => ['required'],
            "{$addressType}.email" => $emailRules,
            "{$addressType}.address" => ['required', 'array', 'min:1'],
            "{$addressType}.city" => ['required'],
            "{$addressType}.country" => core()->isCountryRequired() ? ['required'] : ['nullable'],
            "{$addressType}.state" => core()->isStateRequired() ? ['required'] : ['nullable'],
            "{$addressType}.postcode" => $postcodeRules,
            "{$addressType}.phone" => $phoneRules,
        ]);

        if ($addressType == 'billing') {
            $this->mergeWithRules([
                "{$addressType}.vat_id" => [(new VatIdRule)->setCountry($this->input('billing.country'))],
            ]);
        }
    }

    /**
     * Merge additional rules.
     */
    private function mergeWithRules($rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }

    private function customerIsMessengerOnboarded(): bool
    {
        $customer = auth()->guard('customer')->user();

        return $customer && ! empty($customer->chatpion_subscribe_id);
    }

    private function channelRelaxesAddressFields(): bool
    {
        return in_array(
            optional(core()->getCurrentChannel())->code,
            ['mmfood', 'kimono'],
            true
        );
    }
}
