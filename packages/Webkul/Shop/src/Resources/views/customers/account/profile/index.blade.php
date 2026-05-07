<x-shop::layouts.account>
    <x-slot:title>
        @lang('shop::app.customers.account.profile.index.title')
    </x-slot>

    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="profile" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="flex-auto mx-4 max-md:mx-6 max-sm:mx-4">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <a class="grid md:hidden" href="{{ route('shop.customers.account.index') }}">
                    <span class="text-2xl icon-arrow-left rtl:icon-arrow-right"></span>
                </a>
                <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                    @lang('shop::app.customers.account.profile.index.title')
                </h2>
            </div>
            {!! view_render_event('bagisto.shop.customers.account.profile.edit_button.before') !!}
            <a href="{{ route('shop.customers.account.profile.edit') }}" class="secondary-button border-zinc-200 px-5 py-3 font-normal max-md:rounded-lg max-md:py-2 max-sm:py-1.5 max-sm:text-sm">
                @lang('shop::app.customers.account.profile.index.edit')
            </a>
            {!! view_render_event('bagisto.shop.customers.account.profile.edit_button.after') !!}
        </div>

        <!-- Clean profile card -->
        <div class="rounded-2xl border border-zinc-200 bg-white overflow-hidden shadow-sm max-w-lg">

            <!-- Avatar + Name area -->
            <div class="flex items-center gap-5 px-7 py-6 bg-gradient-to-r from-slate-50 to-blue-50 border-b border-zinc-100">
                <div class="flex-shrink-0">
                    <div class="h-16 w-16 rounded-full bg-navyBlue flex items-center justify-center text-white text-2xl font-bold select-none" v-pre>
                        {{ strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1)) }}
                    </div>
                </div>
                <div v-pre>
                    <p class="text-xl font-semibold text-gray-900">{{ $customer->first_name }} {{ $customer->last_name }}</p>
                    <p class="text-sm text-zinc-500 mt-0.5">{{ $customer->email }}</p>
                </div>
            </div>

            <!-- Info rows -->
            <div class="divide-y divide-zinc-100">
                {!! view_render_event('bagisto.shop.customers.account.profile.first_name.before') !!}
                <div class="flex items-center px-7 py-4">
                    <span class="icon-profile text-xl text-zinc-400 ltr:mr-3 rtl:ml-3 flex-shrink-0"></span>
                    <span class="text-sm text-zinc-500 w-32 flex-shrink-0">@lang('shop::app.customers.account.profile.index.first-name')</span>
                    <span class="text-sm font-medium text-gray-900" v-pre>{{ $customer->first_name }} {{ $customer->last_name }}</span>
                </div>
                {!! view_render_event('bagisto.shop.customers.account.profile.last_name.after') !!}

                {!! view_render_event('bagisto.shop.customers.account.profile.email.before') !!}
                <div class="flex items-center px-7 py-4">
                    <span class="icon-mail text-xl text-zinc-400 ltr:mr-3 rtl:ml-3 flex-shrink-0"></span>
                    <span class="text-sm text-zinc-500 w-32 flex-shrink-0">@lang('shop::app.customers.account.profile.index.email')</span>
                    <span class="text-sm font-medium text-gray-900 no-underline" v-pre>{{ $customer->email }}</span>
                </div>
                {!! view_render_event('bagisto.shop.customers.account.profile.email.after') !!}
            </div>
        </div>

        <!-- Delete profile -->
        {!! view_render_event('bagisto.shop.customers.account.profile.delete.before') !!}
        <div class="mt-8">
            <x-shop::form action="{{ route('shop.customers.account.profile.destroy') }}">
                <x-shop::modal>
                    <x-slot:toggle>
                        <div class="py-3 primary-button rounded-2xl px-11 max-md:hidden max-md:rounded-lg">
                            @lang('shop::app.customers.account.profile.index.delete-profile')
                        </div>
                        <div class="rounded-2xl py-3 text-center font-medium text-red-500 max-md:w-full max-md:max-w-full max-md:py-1.5 md:hidden">
                            @lang('shop::app.customers.account.profile.index.delete-profile')
                        </div>
                    </x-slot>
                    <x-slot:header>
                        <h2 class="text-2xl font-medium max-md:text-base">@lang('shop::app.customers.account.profile.index.enter-password')</h2>
                    </x-slot>
                    <x-slot:content>
                        <x-shop::form.control-group class="!mb-0">
                            <x-shop::form.control-group.control type="password" name="password" class="px-6 py-4" rules="required" placeholder="Enter your password" />
                            <x-shop::form.control-group.error class="text-left" control-name="password" />
                        </x-shop::form.control-group>
                    </x-slot>
                    <x-slot:footer>
                        <button type="submit" class="flex py-3 primary-button rounded-2xl px-11 max-md:rounded-lg max-md:px-6 max-md:text-sm">
                            @lang('shop::app.customers.account.profile.index.delete')
                        </button>
                    </x-slot>
                </x-shop::modal>
            </x-shop::form>
        </div>
        {!! view_render_event('bagisto.shop.customers.account.profile.delete.after') !!}

    </div>
</x-shop::layouts.account>
