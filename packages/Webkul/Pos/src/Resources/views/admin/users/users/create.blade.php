<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.users.users.create.title')
    </x-slot:title>

    <x-admin::form
        :action="route('admin.pos.users.store')"
        enctype="multipart/form-data"
    >
        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-white font-bold">
                @lang('pos::app.admin.users.users.create.title')
            </p>

            @if (bouncer()->hasPermission('pos.users.users.create'))
                <div class="flex gap-x-2.5 items-center">
                    <!-- Back Button -->
                    <a
                        href="{{ route('admin.pos.users.index') }}"
                        class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    >
                        @lang('pos::app.admin.users.users.create.back-btn')
                    </a>

                    <!-- Save Button -->
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('pos::app.admin.users.users.create.save-btn')
                    </button>
                </div>
            @endif
        </div>

        <!-- Full Panel -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.users.create.general')
                    </p>

                    <!-- UserName -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.username')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="username"
                            :value="old('username')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.username')"
                            :placeholder="trans('pos::app.admin.users.users.create.username')"
                        />

                        <x-admin::form.control-group.error control-name="username" />
                    </x-admin::form.control-group>

                    <!-- First Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.first-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="firstname"
                            :value="old('firstname')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.first-name')"
                            :placeholder="trans('pos::app.admin.users.users.create.first-name')"
                        />

                        <x-admin::form.control-group.error control-name="firstname" />
                    </x-admin::form.control-group>

                    <!-- LastName -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.last-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="lastname"
                            :value="old('lastname')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.last-name')"
                            :placeholder="trans('pos::app.admin.users.users.create.last-name')"
                        />

                        <x-admin::form.control-group.error control-name="lastname" />
                    </x-admin::form.control-group>

                    <!-- Email -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="email"
                            :value="old('email')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.email')"
                            :placeholder="trans('pos::app.admin.users.users.create.email')"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.users.create.outlet-and-status')
                    </p>

                    <!-- User Status -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            :value="1"
                        />

                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.outlet')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="outlet_id"
                            :value="old('outlet_id')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.outlet')"
                            :placeholder="trans('pos::app.admin.users.users.create.outlet')"
                        >
                            <option value="">
                                @lang('pos::app.admin.users.users.create.select-outlet')
                            </option>

                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="outlet_id" />
                    </x-admin::form.control-group>
                </div>
            </div>
            
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.users.create.password')
                    </p>

                    <!-- password -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="password"
                            :value="old('password')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.password')"
                            :placeholder="trans('pos::app.admin.users.users.create.password')"
                        />

                        <x-admin::form.control-group.error control-name="password" />
                    </x-admin::form.control-group>

                    <!-- Confirm Password -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pos::app.admin.users.users.create.confirm-password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="password_confirmation"
                            :value="old('password_confirmation')"
                            rules="required"
                            :label="trans('pos::app.admin.users.users.create.confirm-password')"
                            :placeholder="trans('pos::app.admin.users.users.create.confirm-password')"
                        />

                        <x-admin::form.control-group.error control-name="password_confirmation" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('pos::app.admin.users.users.create.image')
                    </p>

                    <div class="flex flex-col gap-2 w-[40%] mt-5">
                        <p class="text-gray-800 dark:text-white font-medium">
                            @lang('pos::app.admin.users.users.create.user-image')
                        </p>

                        <x-admin::media.images name="image" />
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>