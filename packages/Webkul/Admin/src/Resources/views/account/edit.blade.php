<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.account.edit.title')
    </x-slot>

    <!-- Input Form -->
    <x-admin::form
        :action="route('admin.account.update')"
        enctype="multipart/form-data"
        method="PUT"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.account.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                 <!-- Back Button -->
                <a
                    href="{{ route('admin.dashboard.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.account.edit.back-btn')
                </a>

                <!-- Save Button -->
                <div class="flex items-center gap-x-2.5">
                    <button 
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.account.edit.save-btn')
                    </button>
                </div>
            </div>
        </div>

        <!-- Full Panel -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
             <!-- Left sub Component -->
             <div class="flex flex-1 flex-col gap-2">
                 <!-- General -->
                 <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.account.edit.general')
                    </p>

                    <!-- Image -->
                    <x-admin::form.control-group>
                        <x-admin::media.images
                            name="image"
                            :uploaded-images="$user->image ? [['id' => 'image', 'url' => $user->image_url]] : []"
                        />
                    </x-admin::form.control-group>

                    <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                        @lang('admin::app.account.edit.upload-image-info')
                    </p>

                    <!-- Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            rules="required"
                            :value="old('name') ?: $user->name"
                            :label="trans('admin::app.account.edit.name')"
                            :placeholder="trans('admin::app.account.edit.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Email -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            name="email"
                            id="email"
                            rules="required"
                            :value="old('email') ?: $user->email"
                            :label="trans('admin::app.account.edit.email')"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>
                    <!-- Interface Language -->
                    <x-admin::form.control-group class="!mb-0 mt-4">
                        <x-admin::form.control-group.label>
                            Interface Language
                        </x-admin::form.control-group.label>

                        <select
                            name="locale"
                            class="flex w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            @php
                                $currentLocale = $user->locale ?? app()->getLocale() ?? 'en';
                                try {
                                    $availableLocales = \Illuminate\Support\Facades\DB::table('locales')->pluck('name', 'code')->toArray();
                                } catch(\Throwable $e) {
                                    $availableLocales = ['en' => 'English'];
                                }
                                if (empty($availableLocales)) $availableLocales = ['en' => 'English'];
                            @endphp
                            @foreach($availableLocales as $code => $name)
                                <option value="{{ $code }}" {{ $currentLocale === $code ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </x-admin::form.control-group>

                </div>
             </div>

             <!-- Right sub-component -->
             <div class="flex w-[360px] max-w-full flex-col gap-2 max-md:w-full">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.account.edit.change-password')
                        </p>
                    </x-slot>

                     <!-- Change Account Password -->
                    <x-slot:content>
                        <!-- Current Password -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.account.edit.current-password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="current_password"
                                rules="required|min:6"
                                :label="trans('admin::app.account.edit.current-password')"
                                :placeholder="trans('admin::app.account.edit.current-password')"
                            />

                            <x-admin::form.control-group.error control-name="current_password" />
                        </x-admin::form.control-group>

                        <!-- Password -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.account.edit.password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="password"
                                rules="min:6"
                                :placeholder="trans('admin::app.account.edit.password')"
                                ref="password"
                            />

                            <x-admin::form.control-group.error control-name="password" />
                        </x-admin::form.control-group>

                        <!-- Confirm Password -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.account.edit.confirm-password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="password_confirmation"
                                rules="confirmed:@password"
                                :label="trans('admin::app.account.edit.confirm-password')"
                                :placeholder="trans('admin::app.account.edit.confirm-password')"
                            />

                            <x-admin::form.control-group.error control-name="password_confirmation" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
             </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
