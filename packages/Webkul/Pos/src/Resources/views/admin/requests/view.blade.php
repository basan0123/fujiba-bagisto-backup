<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.requests.view.title', ['id' => $productRequest->id])
    </x-slot:title>

    <x-admin::form
        enctype="multipart/form-data"
        method="PUT"
    >
        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-white font-bold">
                @lang('pos::app.admin.requests.view.title', ['id' => $productRequest->id])
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.pos.requests.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('pos::app.admin.requests.view.back-btn')
                </a>

                @if ($productRequest->request_status != 1)
                    <!-- Save Button -->
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('pos::app.admin.requests.view.btn-title')
                    </button>
                @endif
            </div>
        </div>

        <!-- Full Panel -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="overflow-x-auto box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.requests.view.user-info.title')
                    </p>

                    <x-admin::table style="min-width: unset !important;">
                        <x-admin::table.tbody class="dark:bg-gray-900">
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.user-info.name')
                                </x-admin::table.td>
                                
                                <x-admin::table.td>
                                    <a
                                        href="{{ route('admin.pos.users.edit', $productRequest->user?->id) }}"
                                        class="text-blue-500 hover:underline"
                                        target="_blank" 
                                    >
                                        {{ $productRequest->user?->firstname }}
                                        {{ $productRequest->user?->lastname }}
                                    </a>
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.user-info.email')
                                </x-admin::table.td>
                                
                                <x-admin::table.td class="dark:text-white">
                                    {{ $productRequest->user->email }}
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.user-info.outlet-name')
                                </x-admin::table.td>
                                
                                <x-admin::table.td class="dark:text-white">
                                    <a
                                        href="{{ route('admin.pos.users.edit', $productRequest->user->id) }}"
                                        class="text-blue-500 hover:underline"
                                        target="_blank" 
                                    >
                                        {{ $productRequest->user->outlet?->name }}
                                    </a>
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.user-info.outlet-address')
                                </x-admin::table.td>
                                
                                <x-admin::table.td class="dark:text-white">
                                    {{ $productRequest->user->outlet?->address }},
                                    {{ $productRequest->user->outlet?->state }},
                                    {{ $productRequest->user->outlet?->country }},
                                    {{ $productRequest->user->outlet?->postcode }}
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.user-info.outlet-inventory')
                                </x-admin::table.td>
                                
                                <x-admin::table.td>
                                    <a
                                        href="{{ route('admin.settings.inventory_sources.edit', $productRequest->user->outlet?->inventory_source?->id) }}"
                                        class="text-blue-500 hover:underline"
                                        target="_blank" 
                                    >
                                        {{ $productRequest->user?->outlet?->inventory_source?->name }}
                                    </a>
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        </x-admin::table.tbody>
                    </x-admin::table>
                </div>                
            </div>
            
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="overflow-x-auto box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.requests.view.request-info.title')
                    </p>

                    <x-admin::table style="min-width: unset !important;">
                        <x-admin::table.tbody class="dark:bg-gray-900">
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.request-info.product-name')
                                </x-admin::table.td>
                                
                                <x-admin::table.td>
                                    <a
                                        href="{{ route('admin.catalog.products.edit', $productRequest->product?->id) }}"
                                        class="text-blue-500 hover:underline"
                                        target="_blank" 
                                    >
                                        {{ $productRequest->product?->name }}
                                    </a>
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.request-info.requested-qty')
                                </x-admin::table.td>
                                
                                <x-admin::table.td class="dark:text-white">
                                    <p class="text-gray-600 dark:text-white">
                                        @lang('pos::app.admin.requests.view.request-info.qty-value', ['qty' => $productRequest->requested_quantity])
                                    </p>
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.request-info.request-date')
                                </x-admin::table.td>
                                
                                <x-admin::table.td class="dark:text-white">
                                    {{ $productRequest->created_at }}
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>

                            <x-admin::table.tbody.tr>
                                <x-admin::table.td class="dark:text-white">
                                    @lang('pos::app.admin.requests.view.request-info.comment')
                                </x-admin::table.td>
                                
                                <x-admin::table.td class="dark:text-white">
                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        name="comment"
                                        id="comment"
                                        class="overflow-hidden"
                                        value="{{ $productRequest->comment }}"
                                        rows="5"
                                        readonly
                                    />
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        </x-admin::table.tbody>
                    </x-admin::table>

                    <x-admin::form.control-group class="px-6 mt-2">
                        <x-admin::form.control-group.label>
                            @lang('pos::app.admin.requests.view.request-info.status.title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="request_status"
                            :value="old('request_status') ?: $productRequest->request_status"
                            rules="required"
                            :label="trans('pos::app.admin.requests.view.request-info.status.title')"
                        >
                            <option value="0">
                                @lang('pos::app.admin.requests.view.request-info.status.options.pending')
                            </option>

                            <option value="1">
                                @lang('pos::app.admin.requests.view.request-info.status.options.complete')
                            </option>

                            <option value="2">
                                @lang('pos::app.admin.requests.view.request-info.status.options.decline')
                            </option>            
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="request_status" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>