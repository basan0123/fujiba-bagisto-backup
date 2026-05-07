<template>
    <div>
        <div class="control-group" :class="[errors.has('address-form.' + type + '[first_name]') ? 'has-error' : '']">
            <input type="text" :name="type + '[first_name]'" class="control" v-model="address.first_name" v-validate="'required'" :placeholder="$t('First Name')" :data-vv-as="$t('First Name')" onkeypress="return (event.charCode > 64 &&
            event.charCode < 91) || (event.charCode > 96 && event.charCode < 123)"/>
            <label>{{ $t('First Name') }}</label>
            <span class="control-error" v-if="errors.has('address-form.' + type + '[first_name]')">{{ errors.first('address-form.' + type + '[first_name]') }}</span>
        </div>

        <div class="control-group" :class="[errors.has('address-form.' + type + '[last_name]') ? 'has-error' : '']">
            <input type="text" :name="type + '[last_name]'" class="control" v-model="address.last_name" v-validate="'required'" :placeholder="$t('Last Name')" :data-vv-as="$t('Last Name')" onkeypress="return (event.charCode > 64 &&
            event.charCode < 91) || (event.charCode > 96 && event.charCode < 123)"/>
            <label>{{ $t('Last Name') }}</label>
            <span class="control-error" v-if="errors.has('address-form.' + type + '[last_name]')">{{ errors.first('address-form.' + type + '[last_name]') }}</span>
        </div>

        <div class="control-group" :class="[errors.has('address-form.' + type + '[email]') ? 'has-error' : '']">
            <input type="email" :name="type + '[email]'" class="control" v-model="address.email" v-validate="messengerOptional ? 'email' : 'required|email'" :placeholder="$t('Email')" :data-vv-as="$t('Email')"/>
            <label>{{ $t('Email') }}</label>
            <span class="control-error" v-if="errors.has('address-form.' + type + '[email]')">{{ errors.first('address-form.' + type + '[email]') }}</span>
        </div>

        <div class="control-group" v-if="streetLines && streetLines > 0" v-for="n in parseInt(streetLines)" style="margin-top: -15px;">
            <input type="text" name="address1[]" class="control" v-model="address.address[n-1]" :placeholder="$t('Street Address number', {number:  + n})">
            <label>{{ $t('Street Address number', {number:  + n }) }}</label>
        </div>

        <checkout-country-state :address="address" :type="type"></checkout-country-state>

        <div class="control-group" :class="[errors.has('address-form.' + type + '[city]') ? 'has-error' : '']">
            <input type="text" :name="type + '[city]'" class="control" v-model="address.city" v-validate="'required'" :placeholder="$t('City')" :data-vv-as="$t('City')"/>
            <label>{{ $t('City') }}</label>
            <span class="control-error" v-if="errors.has('address-form.' + type + '[city]')">{{ errors.first('address-form.' + type + '[city]') }}</span>
        </div>

        <div class="control-group" :class="[errors.has('address-form.' + type + '[postcode]') ? 'has-error' : '']">
            <input type="text" :name="type + '[postcode]'" class="control" v-model="address.postcode"
                   v-validate="messengerOptional ? '' : 'required'" :placeholder="$t('Postal Code')" :data-vv-as="$t('Postal Code')"
                   @blur="lookupPostcode" @input="onPostcodeInput"/>
            <label>{{ $t('Postal Code') }}</label>
            <span v-if="postcodeStatus === 'looking'" class="postcode-hint">…</span>
            <span v-if="postcodeStatus === 'notfound'" class="postcode-hint error">Postcode not found</span>
            <span class="control-error" v-if="errors.has('address-form.' + type + '[postcode]')">Postal Code is Required.</span>
        </div>

        <div class="control-group" :class="[errors.has('address-form.' + type + '[phone]') ? 'has-error' : '']">
            <input type="number" :name="type + '[phone]'" class="control" v-model="address.phone" v-validate="'required'" :placeholder="$t('Phone')" :data-vv-as="$t('City')"/>
            <label>{{ $t('Phone') }}</label>
            <span class="control-error" v-if="errors.has('address-form.' + type + '[phone]')">Phone Number is required.</span>
        </div>
    </div>
</template>

<script>
    import CheckoutCountryState from './country-state';

    export default {
        name: 'checkout-address',

        components: { CheckoutCountryState },

        props: [ 'address', 'type', 'cart' ],

        data () {
            return {
                streetLines: 2,
                postcodeStatus: '',  // '', 'looking', 'found', 'notfound'
                _postcodeTimer: null,
            }
        },

        inject: [ '$validator' ],

        computed: {
            // Server sets window.MESSENGER_OPTIONAL_FIELDS = true when the
            // customer logged in via Messenger on mmfood/kimono. Email and
            // postcode become optional in that case; the backend fills email
            // from the stored customer record when blank.
            messengerOptional () {
                return typeof window !== 'undefined' && window.MESSENGER_OPTIONAL_FIELDS === true;
            },
        },

        mounted () {
            this.getConfigData()
        },

        methods: {
            // Debounced 7-digit JP postcode lookup → autofill state + city.
            // Uses our /api/v1/postcode/{code} endpoint (channel-agnostic, cached 24h).
            onPostcodeInput () {
                if (this._postcodeTimer) clearTimeout(this._postcodeTimer);
                this.postcodeStatus = '';
                var clean = String(this.address.postcode || '').replace(/[^0-9]/g, '');
                if (clean.length !== 7) return;
                this._postcodeTimer = setTimeout(this.lookupPostcode, 350);
            },
            lookupPostcode () {
                var clean = String(this.address.postcode || '').replace(/[^0-9]/g, '');
                if (clean.length !== 7) return;
                this.postcodeStatus = 'looking';
                var self = this;
                this.$http.get('/api/v1/postcode/' + clean)
                    .then(function (resp) {
                        var d = resp && resp.data && resp.data.data;
                        if (!d || !d.found) { self.postcodeStatus = 'notfound'; return; }
                        self.postcodeStatus = 'found';
                        // Only fill if user hasn't typed something there.
                        if (!self.address.city)  self.address.city  = d.city  || '';
                        if (!self.address.state) self.address.state = d.state || '';
                    })
                    .catch(function () { self.postcodeStatus = 'notfound'; });
            },

            getConfigData () {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                let street_lines = 'customer.settings.address.street_lines';
                const configKeys = [
                    street_lines,
                ];

                this.$http.get('/api/v1/core-configs', {
                    params: {
                        _config: configKeys
                    }
                })
                    .then(function(response) {
                        EventBus.$emit('hide-ajax-loader');

                        if (response.data.data[street_lines]) {
                            this_this.streetLines = response.data.data[street_lines];

                            this_this.streetLines = this_this.streetLines - 1;
                        }
                    })
                    .catch(function (error) {});
            },
        }
    };
</script>

<style scoped lang="scss">
    .postcode-hint {
        display:block; font-size:11px; color:#888; margin-top:-8px; margin-bottom:6px;
        &.error { color:#c8220a; }
    }
</style>
