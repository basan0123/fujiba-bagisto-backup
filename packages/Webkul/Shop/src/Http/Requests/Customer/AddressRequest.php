<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;
use Webkul\Customer\Rules\VatIdRule;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Messenger-onboarded customers (chatpion_subscribe_id present) lack a
        // real email/postcode/phone regardless of channel — on Fujiba the
        // kimono channel is coded `default`, so a channel-name gate misses
        // them. Drive the relax off the customer flag and keep the legacy
        // channel list as a fallback for guest checkout on mmfood storefronts.
        $messengerCustomer = $this->customerIsMessengerOnboarded();
        $channelRelaxes = $messengerCustomer || $this->channelRelaxesAddressFields();

        return [
            'company_name' => ['nullable'],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'address' => ['required', 'array', 'min:1'],
            'country' => core()->isCountryRequired() ? ['required'] : ['nullable'],
            'state' => core()->isStateRequired() ? ['required'] : ['nullable'],
            'city' => ['required', 'string'],
            'postcode' => $channelRelaxes
                ? ['nullable', new PostCode]
                : (core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode]),
            'phone' => $channelRelaxes ? ['nullable', new PhoneNumber] : ['required', new PhoneNumber],
            'vat_id' => [(new VatIdRule)->setCountry($this->input('country'))],
            'email' => $messengerCustomer ? ['nullable', 'email'] : ['required', 'email'],
        ];
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

    /**
     * Attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'address.*' => 'address',
        ];
    }
}
