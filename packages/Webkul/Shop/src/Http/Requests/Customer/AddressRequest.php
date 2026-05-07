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
        // Messenger-logged-in customers on mmfood/kimono can save addresses
        // with a blank email and postcode. AddressController falls back to
        // the stored customer email when needed.
        $messengerOptional = $this->messengerFieldsAreOptional();

        return [
            'company_name' => ['nullable'],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'address' => ['required', 'array', 'min:1'],
            'country' => core()->isCountryRequired() ? ['required'] : ['nullable'],
            'state' => core()->isStateRequired() ? ['required'] : ['nullable'],
            'city' => ['required', 'string'],
            'postcode' => $messengerOptional
                ? ['nullable', new PostCode]
                : (core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode]),
            'phone' => ['required', new PhoneNumber],
            'vat_id' => [(new VatIdRule)->setCountry($this->input('country'))],
            'email' => $messengerOptional ? ['nullable', 'email'] : ['required', 'email'],
        ];
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
