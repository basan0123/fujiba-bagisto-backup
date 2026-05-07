<template>
    <div class="content">
        <custom-header :title="$t('Edit Profile')"></custom-header>

        <form action="POST" @submit.prevent="validateBeforeSubmit">
            <div class="form-container">
                <div class="control-group" :class="[errors.has('first_name') ? 'has-error' : '']">
                    <input type="text" name="first_name" class="control" v-model="customer.first_name" v-validate="'required'" :placeholder="$t('First Name')" :data-vv-as="$t('First Name')"/>
                    <label>{{ $t('First Name') }}</label>
                    <span class="control-error" v-if="errors.has('first_name')">{{ errors.first('first_name') }}</span>
                </div>

                <div class="control-group" :class="[errors.has('last_name') ? 'has-error' : '']">
                    <input type="text" name="last_name" class="control" v-model="customer.last_name" v-validate="'required'" :placeholder="$t('Last Name')" :data-vv-as="$t('Last Name')"/>
                    <label>{{ $t('Last Name') }}</label>
                    <span class="control-error" v-if="errors.has('last_name')">{{ errors.first('last_name') }}</span>
                </div>

                <div class="control-group" :class="[errors.has('email') ? 'has-error' : '']">
                    <input type="text" name="email" class="control" v-model="customer.email" v-validate="'required|email'" :placeholder="$t('Email Address')" :data-vv-as="$t('Email Address')"/>
                    <label>{{ $t('Email Address') }}</label>
                    <span class="control-error" v-if="errors.has('email')">{{ errors.first('email') }}</span>
                </div>

                <div class="control-group" :class="[errors.has('gender') ? 'has-error' : '']">
                    <select name="gender" class="control" v-model="customer.gender" v-validate="'required'" :placeholder="$t('Gender')" :data-vv-as="$t('Gender')">
                        <option value="Male">{{ $t('Male') }}</option>
                        <option value="Female">{{ $t('Female') }}</option>
                        <option value="Other">{{ $t('Other') }}</option>
                    </select>

                    <label>{{ $t('Gender') }}</label>
                    <span class="control-error" v-if="errors.has('gender')">{{ errors.first('gender') }}</span>
                </div>

                <div class="control-group">
                    <input type="date" name="date_of_birth" class="control" v-model="customer.date_of_birth" :placeholder="$t('Date Of Birth')"/>
                    <label>{{ $t('Date Of Birth') }}</label>
                </div>

                <div class="control-group" :class="[errors.has('password') ? 'has-error' : '']">
                    <input type="password" name="password" class="control" v-model="customer.password" v-validate="'min:6'" :placeholder="$t('Password')" :data-vv-as="$t('Password')"/>
                    <label>{{ $t('Password') }}</label>
                    <span class="control-error" v-if="errors.has('password')">{{ errors.first('password') }}</span>
                </div>

                <div class="control-group" :class="[errors.has('password_confirmation') ? 'has-error' : '']">
                    <input type="password" name="password_confirmation" class="control" v-model="customer.password_confirmation" v-validate="'min:6|confirmed:password'" :placeholder="$t('Confirm Password')" :data-vv-as="$t('Confirm Password')"/>
                    <label>{{ $t('Confirm Password') }}</label>
                    <span class="control-error" v-if="errors.has('password_confirmation')">{{ errors.first('password_confirmation') }}</span>
                    <div class="control-info success">
                        <span class="dot"></span>

                        <span class="info">
                            {{ $t('Minimum length of this field must be equal or greater than 6 characters.') }}
                        </span>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-black btn-lg" :disabled="loading">{{ $t('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
    import CustomHeader from '../../../layouts/custom-header';

    export default {
        name: 'edit-profile',

        props: ['customer'],

        data () {
            return {
                loading: false
            }
        },

        components: { CustomHeader },

        methods: {
            validateBeforeSubmit () {
                this.loading = true;

                this.$validator.validateAll().then((result) => {
                    if (result) {
                        this.updateProfile()
                    } else {
                        this.loading = false;
                    }
                });
            },

            updateProfile () {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.put("/api/v1/customer/profile", this.customer)
                    .then(function(response) {
                        this_this.loading = false;

                        this_this.$toasted.show(response.data.message, { type: 'success' })

                        EventBus.$emit('hide-ajax-loader');

                        EventBus.$emit('customer-info-updated', this_this.customer);

                        this_this.$router.push({name: 'dashboard'})
                    })
                    .catch(function (error) {
                        var errors = error.response.data.errors;

                        for (name in errors) {
                            if (errors.hasOwnProperty(name)) {
                                this_this.errors.add(name, errors[name][0])
                                this_this.$toasted.show(errors[name][0], { type: 'error' })
                            }
                        }

                        this_this.loading = false;
                    })
            }
        }
    }

</script>

<style scoped lang="scss">
    .content {
        position: absolute;
        bottom: 0;
        top: 0;
        width: 100%;
        background: #faf8f5;
        overflow-y: auto;

        .form-container {
            padding: 18px 14px 80px;
        }
    }

    /* Soften field style across the form to match chat-checkout. */
    ::v-deep .control-group {
        margin-bottom: 12px;
        background: #fff;
        border-radius: 12px;
        border: 1.5px solid #e2d5c8;
        padding: 6px 12px;
        position: relative;
        transition: border-color 0.15s;
    }
    ::v-deep .control-group.has-error { border-color: #c8220a; }
    ::v-deep .control-group:focus-within { border-color: var(--p, #c8420a); }

    ::v-deep .control-group .control {
        border: 0 !important;
        outline: none !important;
        background: transparent !important;
        font-size: 14px;
        padding: 6px 0 !important;
        width: 100%;
        box-shadow: none !important;
        color: #1a0800;
    }
    ::v-deep .control-group label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #9a7a5a;
        margin-bottom: 2px;
        text-transform: none;
    }
    ::v-deep .control-group select.control {
        appearance: none;
        -webkit-appearance: none;
        background:
            url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8' width='10' height='8' fill='%239a7a5a'><polygon points='0,0 12,0 6,8'/></svg>")
            no-repeat right 2px center !important;
        padding-right: 18px !important;
        font-size: 13px;
    }
    ::v-deep .control-info {
        font-size: 10px;
        color: #9a7a5a;
        margin-top: 4px;
        display: flex; align-items: center; gap: 4px;
    }
    ::v-deep .control-info.success { color: #2d7a4a; }
    ::v-deep .control-info .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: currentColor;
        display: inline-block;
    }
    ::v-deep .control-error {
        display: block;
        font-size: 10px;
        color: #c8220a;
        margin-top: 4px;
    }

    .button-group {
        margin-top: 18px;
    }
    ::v-deep .button-group .btn {
        width: 100%;
        background: var(--p, #c8420a) !important;
        color: #fff !important;
        border: 0 !important;
        border-radius: 12px !important;
        padding: 14px !important;
        font-size: 15px;
        font-weight: 700;
    }
    ::v-deep .button-group .btn:disabled { opacity: 0.6; }
</style>
