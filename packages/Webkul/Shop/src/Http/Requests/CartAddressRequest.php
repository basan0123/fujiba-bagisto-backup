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
        // Messenger-logged-in customers on mmfood/kimono can leave email and
        // postcode blank; OnepageController fills email from the customer
        // record before saveAddresses() runs.
        $messengerOptional = $this->messengerFieldsAreOptional();

        $emailRules = $messengerOptional
            ? ['nullable', 'email']
            : ['required', 'email'];

        $postcodeRules = $messengerOptional
            ? ['nullable', new PostCode]
            : (core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode]);

        $phoneRules = $messengerOptional
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

    private function messengerFieldsAreOptional(): bool
    {
        $customer = auth()->guard('customer')->user();

        if (! $customer || empty($customer->chatpion_subscribe_id)) {
            return false;
        }

        return in_array(
            optional(core()->getCurrentChannel())->code,
            ['mmfood', 'kimono'],
            true
        );
    }
}
