@push('styles')
<style>
    .flatpickr-calendar { display:none !important; }
    .flatpickr-calendar.open { display:block !important; width:auto !important; max-width:320px; left:auto !important; right:auto !important; }
    .flatpickr-months svg, .flatpickr-prev-month svg, .flatpickr-next-month svg { width:14px; height:14px; }
</style>
@endpush

<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.account.orders.title')
    </x-slot>

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="orders" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-auto max-md:mx-6 max-sm:mx-4">
        <div class="mb-8 flex items-center max-sm:mb-5">
            <!-- Back Button -->
            <a
                class="grid md:hidden"
                href="{{ route('shop.customers.account.index') }}"
            >
                <span class="icon-arrow-left rtl:icon-arrow-right text-2xl"></span>
            </a>

            <h2 class="text-2xl font-medium max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                @lang('shop::app.customers.account.orders.title')
            </h2>
        </div>

        {!! view_render_event('bagisto.shop.customers.account.orders.list.before') !!}

        <!-- For Desktop View -->
        <div class="max-md:hidden">
            <x-shop::datagrid :src="route('shop.customers.account.orders.index')" />
        </div>

        <!-- For Mobile View -->
        <div class="md:hidden">
            <x-shop::datagrid :src="route('shop.customers.account.orders.index')">
                <!-- Datagrid Header -->
                <template #header="{
                    isLoading,
                    available,
                    applied,
                    selectAll,
                    sort,
                    performAction
                }">
                    <div class="hidden"></div>
                </template>

                <template #body="{
                    isLoading,
                    available,
                    applied,
                    selectAll,
                    sort,
                    performAction
                }">
                    @php
                        $sevenDaysAgo = now()->subDays(7)->toDateTimeString();
                    @endphp
                    <template v-if="isLoading">
                        <x-shop::shimmer.datagrid.table.body />
                    </template>
    
                    <template v-else>
                        <template v-for="record in available.records">
                            <div
                                class="relative w-full p-4 border rounded-md transition-all hover:bg-gray-50 active:bg-gray-100 [&>*]:border-0 mb-4 last:mb-0 cursor-pointer"
                                @click="window.location.href = record.actions[0]?.url"
                            >
                                <div class="flex justify-between">
                                    <div class="text-sm font-semibold">
                                        @lang('shop::app.customers.account.orders.order-id'): #@{{ record.increment_id || record.id }}

                                        <p class="text-xs font-normal text-neutral-500">
                                            @{{ record.created_at }}
                                        </p>
                                    </div>

                                    <p v-html="record.status"></p>
                                </div>

                                <div class="mt-2.5 text-xs font-normal text-neutral-500">
                                    @lang('shop::app.customers.account.orders.subtotal')

                                    <p class="text-xl font-semibold text-black">
                                        @{{ record.grand_total }}
                                    </p>
                                </div>

                                <!-- Delivery Status Bar (recent orders only) -->
                                <template v-if="(new Date(record.created_at) >= new Date('{{ $sevenDaysAgo }}')) && !['canceled','closed','fraud'].includes(record.raw_status)">
                                    @php
                                        // Delivery step logic (computed inline via Vue):
                                        // step 1 = confirmed (always), 2 = shipped, 3 = in-transit (shipped but not completed), 4 = delivered (completed)
                                    @endphp
                                    <div class="mt-3 pt-3 border-t border-gray-100">
                                        <div class="flex items-center justify-between text-[10px] font-medium text-gray-400 mb-1.5">
                                            <span :class="'text-blue-600 font-bold'">@lang('shop::app.customers.account.orders.delivery-status.confirmed')</span>
                                            <span :class="record.shipped_at ? 'text-blue-600 font-bold' : ''">@lang('shop::app.customers.account.orders.delivery-status.shipped')</span>
                                            <span :class="record.shipped_at && record.raw_status !== 'pending' ? 'text-blue-600 font-bold' : ''">@lang('shop::app.customers.account.orders.delivery-status.in-transit')</span>
                                            <span :class="record.raw_status === 'completed' ? 'text-green-600 font-bold' : ''">@lang('shop::app.customers.account.orders.delivery-status.delivered')</span>
                                        </div>
                                        <div class="relative h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                            <div
                                                class="absolute left-0 top-0 h-full rounded-full transition-all duration-500"
                                                :class="record.raw_status === 'completed' ? 'bg-green-500' : 'bg-blue-500'"
                                                :style="{ width: (record.raw_status === 'completed' ? '100' : (record.shipped_at ? '66' : (record.raw_status === 'processing' ? '33' : '0'))) + '%' }"
                                            ></div>
                                        </div>
                                        <div class="flex justify-between mt-0.5">
                                            <div class="w-2.5 h-2.5 rounded-full border-2 -mt-[7px] relative z-10 bg-blue-500 border-blue-500"></div>
                                            <div class="w-2.5 h-2.5 rounded-full border-2 -mt-[7px] relative z-10" :class="record.shipped_at ? 'bg-blue-500 border-blue-500' : 'bg-white border-gray-300'"></div>
                                            <div class="w-2.5 h-2.5 rounded-full border-2 -mt-[7px] relative z-10" :class="record.shipped_at && record.raw_status !== 'pending' ? 'bg-blue-500 border-blue-500' : 'bg-white border-gray-300'"></div>
                                            <div class="w-2.5 h-2.5 rounded-full border-2 -mt-[7px] relative z-10" :class="record.raw_status === 'completed' ? 'bg-green-500 border-green-500' : 'bg-white border-gray-300'"></div>
                                        </div>
                                    </div>
                                </template>

                                <a
                                    :href="record.actions[0]?.url"
                                    class="absolute inset-0"
                                    aria-label="View order"
                                ></a>
                            </div>
                        </template>
                    </template>
                </template>
            </x-shop::datagrid>
        </div>
    
        {!! view_render_event('bagisto.shop.customers.account.orders.list.after') !!}

    </div>
</x-shop::layouts.account>
