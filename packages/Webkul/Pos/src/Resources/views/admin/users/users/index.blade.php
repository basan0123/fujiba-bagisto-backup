<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.users.users.index.title')
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.users.users.index.title')
        </p>

        <div class="flex items-center gap-2.5 max-sm:flex-wrap">
            @if (bouncer()->hasPermission('pos.users.users.create'))
                <a
                    href="{{ route('admin.pos.users.create') }}"
                    class="primary-button"
                >
                    @lang('pos::app.admin.users.users.index.create-btn')
                </a>
            @endif

            <a
                href="{{ url()->to('/pos') }}"
                target="_blank"
                class="secondary-button"
            >
                @lang('pos::app.admin.users.outlets.index.pos-front')
            </a>
        </div>
    </div>

    <!-- Datagrid -->
    <x-admin::datagrid
        :src="route('admin.pos.users.index')"
        :isMultiRow="true"
    >
        <!-- Datagrid Header -->
        @php
            $hasPermission = bouncer()->hasPermission('pos.users.users.mass-update') || bouncer()->hasPermission('pos.users.users.mass-delete');
        @endphp

        <template #header="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
            </template>

            <template v-else>
                <div class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 items-center border-b px-4 py-2.5 dark:border-gray-800">
                    <div
                        class="flex select-none items-center gap-2.5"
                        v-for="(columnGroup, index) in [['outlet_name', 'user_email', 'full_name'], ['user_id', 'user_name', 'user_status'], ['user_image']]"
                    >
                        @if ($hasPermission)
                            <label
                                class="flex w-max cursor-pointer select-none items-center gap-1"
                                for="mass_action_select_all_records"
                                v-if="! index"
                            >
                                <input
                                    type="checkbox"
                                    name="mass_action_select_all_records"
                                    id="mass_action_select_all_records"
                                    class="peer hidden"
                                    :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                    @change="selectAll"
                                >

                                <span
                                    class="icon-uncheckbox cursor-pointer rounded-md text-2xl"
                                    :class="[
                                        applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checked peer-checked:text-blue-600' : (
                                            applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-blue-600' : ''
                                        ),
                                    ]"
                                >
                                </span>
                            </label>
                        @endif

                        <p class="text-gray-600 dark:text-gray-300">
                            <span class="[&>*]:after:content-['_/_']">
                                <template v-for="column in columnGroup">
                                    <span
                                        class="after:content-['/'] last:after:content-['']"
                                        :class="{
                                            'font-medium text-gray-800 dark:text-white': applied.sort.column == column,
                                            'cursor-pointer hover:text-gray-800 dark:hover:text-white': available.columns.find(columnTemp => columnTemp.index === column)?.sortable,
                                        }"
                                        @click="
                                            available.columns.find(columnTemp => columnTemp.index === column)?.sortable ? sort(available.columns.find(columnTemp => columnTemp.index === column)): {}
                                        "
                                    >
                                        @{{ available.columns.find(columnTemp => columnTemp.index === column)?.label }}
                                    </span>
                                </template>
                            </span>

                            <i
                                class="align-text-bottom text-base text-gray-800 dark:text-white ltr:ml-1.5 rtl:mr-1.5"
                                :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                v-if="columnGroup.includes(applied.sort.column)"
                            ></i>
                        </p>
                    </div>
                </div>
            </template>
        </template>

        <template #body="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
            </template>

            <template v-else>
                <div
                    class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 border-b px-4 py-2.5 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                    v-for="record in available.records"
                >
                    <!-- Outlet Name, Eamil -->
                    <div class="flex gap-2.5">
                        @if ($hasPermission)
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.user_id}`"
                                :id="`mass_action_select_record_${record.user_id}`"
                                :value="record.user_id"
                                class="peer hidden"
                                v-model="applied.massActions.indices"
                            >

                            <label
                                class="icon-uncheckbox peer-checked:icon-checked cursor-pointer rounded-md text-2xl peer-checked:text-blue-600"
                                :for="`mass_action_select_record_${record.user_id}`"
                            ></label>
                        @endif

                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ record.outlet_name }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.user_email }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.full_name }}
                            </p>
                        </div>
                    </div>

                    <!-- User ID, Name, Status -->
                    <div class="flex flex-col gap-1.5">
                        <p class="text-gray-600 dark:text-gray-300">
                            @{{ "@lang('pos::app.admin.users.users.index.datagrid.id-value')".replace(':id', record.user_id) }}
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @{{ record.user_name }}
                        </p>

                        <p :class="[record.user_status ? 'label-active': 'label-info']">
                            @{{ record.user_status ? "@lang('pos::app.admin.users.users.index.datagrid.status.options.active')" : "@lang('pos::app.admin.users.users.index.datagrid.status.options.disable')" }}
                        </p>
                    </div>

                    <!-- Image -->
                    <div class="flex items-center justify-between gap-x-4">
                        <!--  -->
                        <div class="relative">
                            <template v-if="record.user_image">
                                <img
                                    class="max-h-[65px] min-h-[65px] min-w-[65px] max-w-[65px] rounded"
                                    :src="`{{ Storage::url('') }}${record.user_image}`"
                                />
                            </template>
    
                            <template v-else>
                                <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                                    <img src="{{ bagisto_asset('images/product-placeholders/front.svg')}}">
    
                                    <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                        @lang('pos::app.admin.users.users.index.datagrid.profile-image')
                                    </p>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <a :href="record.actions.find(action => action.method === 'GET').url">
                                <span
                                    :class="record.actions.find(action => action.method === 'GET')?.icon"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 max-sm:place-self-center"
                                    :title="record.actions.find(action => action.method === 'GET')?.title"
                                >
                                </span>
                            </a>
                            
                            <a @click="performAction(record.actions.find(action => action.method === 'DELETE'))">
                                <span
                                    :class="record.actions.find(action => action.method === 'DELETE')?.icon"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    :title="record.actions.find(action => action.method === 'DELETE')?.title"
                                >
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </template>
    </x-admin::datagrid>
</x-admin::layouts>
