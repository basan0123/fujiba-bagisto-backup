<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.banks.edit.title')
    </x-slot:title>

    <x-admin::form
        :action="route('admin.pos.banks.update', $posBank->id)"
        method="PUT"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('pos::app.admin.banks.edit.title')
            </p>

            @if (bouncer()->hasPermission('pos.banks.edit'))
                <div class="flex items-center gap-x-2.5">
                    <!-- Back Button -->
                    <a
                        href="{{ route('admin.pos.banks.index') }}"
                        class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    >
                        @lang('pos::app.admin.banks.edit.back-btn')
                    </a>

                    <!-- Save Button -->
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('pos::app.admin.banks.edit.btn-title')
                    </button>
                </div>
            @endif
        </div>

        <!-- Full Panel -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="relative p-4 bg-white dark:bg-gray-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('pos::app.admin.banks.edit.general.title')
                    </p>

                    <!-- Bank Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.banks.edit.general.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            :value="old('name') ?? $posBank->name"
                            rules="required"
                            :placeholder="trans('pos::app.admin.banks.edit.general.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Bank Address -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.banks.edit.general.address')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="address"
                            :value="old('address') ?? $posBank->address"
                            rules="required|address"
                            :placeholder="trans('pos::app.admin.banks.edit.general.address')"
                        />

                        <x-admin::form.control-group.error control-name="address" />
                    </x-admin::form.control-group>

                    <!-- Bank Email -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.banks.edit.general.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="email"
                            :value="old('email') ?? $posBank->email"
                            rules="required"
                            :placeholder="trans('pos::app.admin.banks.edit.general.email')"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <!-- Bank Phone -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.banks.edit.general.phone')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="phone"
                            :value="old('phone') ?? $posBank->phone"
                            rules="required|numeric"
                            :placeholder="trans('pos::app.admin.banks.edit.general.phone')"
                        />

                        <x-admin::form.control-group.error control-name="phone" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <div class="relative p-4 bg-white dark:bg-gray-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('pos::app.admin.banks.edit.agent-and-status.title')
                    </p>

                    <!-- Bank Status -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.banks.edit.agent-and-status.bank-status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            :value="1"
                            :checked="old('status') ?? (bool) $posBank->status"
                        />

                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>

                    <!-- Bank Agent -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.banks.edit.agent-and-status.agent')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="agent_id"
                            :value="old('agent_id') ?? $posBank->agent_id"
                            rules="required"
                            :label="trans('pos::app.admin.banks.edit.agent-and-status.agent')"
                            :placeholder="trans('pos::app.admin.banks.edit.agent-and-status.agent')"
                        >
                            <option value="">
                                @lang('pos::app.admin.banks.edit.agent-and-status.select-agent')
                            </option>

                            @foreach ($agents as $agent)
                                <option
                                    value="{{ $agent->id }}"
                                    {{ isset($agent_id) && $agent_id == $agent->id ? 'selected' : '' }}
                                >
                                    {{ $agent->firstname . ' ' . $agent->lastname }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="agent_id" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
