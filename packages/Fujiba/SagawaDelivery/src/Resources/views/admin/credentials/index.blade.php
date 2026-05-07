<x-admin::layouts>
    <x-slot:title>{{ __('sagawa::app.credentials.title') }}</x-slot>

    @php
        $inputClass = 'w-full rounded-md border px-2.5 py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
        $labelClass = 'block text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1';
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div>
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ __('sagawa::app.credentials.title') }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    {{ __('sagawa::app.credentials.info') }}
                    <span class="ml-1 text-gray-400 dark:text-gray-500">
                        Tenant:
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $tenantId }}</code>
                    </span>
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-3 rounded-md border border-green-200 bg-green-50 p-2.5 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-2.5 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-3 flex flex-col gap-4">
            @foreach ($channels as $channel)
                @php $cred = $credentials[$channel->id] ?? null; @endphp

                <div class="box-shadow rounded bg-white dark:bg-gray-900">
                    <form method="POST" action="{{ route('admin.sagawa.credentials.save') }}">
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channel->id }}">

                        <!-- Card header -->
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5 dark:border-gray-800">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $channel->code }}
                                </span>
                                <span class="text-[11px] text-gray-400">ID {{ $channel->id }}</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs">
                                <input type="hidden" name="enabled" value="0">
                                <label class="flex cursor-pointer items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="enabled" value="1" @checked($cred?->enabled)
                                        class="h-3.5 w-3.5 rounded border-gray-300 dark:border-gray-700">
                                    {{ __('sagawa::app.credentials.enabled') }}
                                </label>

                                <input type="hidden" name="use_sandbox" value="0">
                                <label class="flex cursor-pointer items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="use_sandbox" value="1" @checked($cred?->use_sandbox ?? true)
                                        class="h-3.5 w-3.5 rounded border-gray-300 dark:border-gray-700">
                                    {{ __('sagawa::app.credentials.sandbox') }}
                                </label>

                                <input type="hidden" name="use_sagawa_rate" value="0">
                                <label class="flex cursor-pointer items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="use_sagawa_rate" value="1" @checked($cred?->use_sagawa_rate)
                                        class="h-3.5 w-3.5 rounded border-gray-300 dark:border-gray-700">
                                    {{ __('sagawa::app.credentials.use-sagawa-rate') }}
                                </label>

                                <button type="submit" class="primary-button !h-7 !px-3 !py-0 !text-xs">
                                    {{ __('sagawa::app.credentials.save') }}
                                </button>
                            </div>
                        </div>

                        <!-- Body: 3-column compact grid -->
                        <div class="grid grid-cols-1 gap-x-4 gap-y-2.5 p-4 md:grid-cols-3">
                            <!-- Priority + Prep -->
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.priority') }}</label>
                                <input type="number" min="1" max="999" name="priority"
                                    value="{{ $cred?->priority ?? 20 }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.preparation-days') }}</label>
                                <input type="number" min="0" max="60" name="preparation_days"
                                    value="{{ $cred?->preparation_days ?? 1 }}" class="{{ $inputClass }}">
                            </div>
                            <div></div>

                            <!-- Customer + Password + Endpoint -->
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.customer-id') }}</label>
                                <input type="text" name="api_customer_id"
                                    value="{{ $cred?->api_customer_id }}"
                                    placeholder="{{ $cred ? __('sagawa::app.credentials.leave-blank') : '' }}"
                                    class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.password') }}</label>
                                <input type="password" name="api_password"
                                    value="{{ $cred?->api_password }}"
                                    placeholder="{{ $cred ? __('sagawa::app.credentials.leave-blank') : '' }}"
                                    autocomplete="new-password" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.endpoint') }}</label>
                                <input type="url" name="api_endpoint"
                                    value="{{ $cred?->api_endpoint ?? 'https://api.sgsystems.co.jp' }}"
                                    class="{{ $inputClass }}">
                            </div>

                            <!-- Sender section header -->
                            <div class="md:col-span-3 mt-1 border-t border-gray-100 pt-2.5 dark:border-gray-800">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('sagawa::app.credentials.sender') }}
                                </p>
                            </div>

                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.sender-name') }}</label>
                                <input type="text" name="sender_name" value="{{ $cred?->sender_name }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.sender-postcode') }}</label>
                                <input type="text" name="sender_postcode" value="{{ $cred?->sender_postcode }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.sender-phone') }}</label>
                                <input type="text" name="sender_phone" value="{{ $cred?->sender_phone }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.sender-prefecture') }}</label>
                                <input type="text" name="sender_prefecture" value="{{ $cred?->sender_prefecture }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.sender-city') }}</label>
                                <input type="text" name="sender_city" value="{{ $cred?->sender_city }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.sender-address') }}</label>
                                <input type="text" name="sender_address" value="{{ $cred?->sender_address }}" class="{{ $inputClass }}">
                            </div>

                            <!-- Support section header -->
                            <div class="md:col-span-3 mt-1 border-t border-gray-100 pt-2.5 dark:border-gray-800">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('sagawa::app.credentials.support') }}
                                </p>
                            </div>

                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.support-phone') }}</label>
                                <input type="text" name="support_phone" value="{{ $cred?->support_phone }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.support-hours') }}</label>
                                <input type="text" name="support_hours" value="{{ $cred?->support_hours }}"
                                    placeholder="9:00–18:00" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.credentials.support-branch') }}</label>
                                <input type="text" name="support_branch" value="{{ $cred?->support_branch }}"
                                    placeholder="佐川急便 ○○営業所" class="{{ $inputClass }}">
                            </div>
                        </div>
                    </form>

                    @if ($cred)
                        <form method="POST" action="{{ route('admin.sagawa.credentials.destroy', $cred->id) }}"
                              class="flex justify-end border-t border-gray-100 px-4 py-2 dark:border-gray-800"
                              onsubmit="return confirm('{{ __('sagawa::app.credentials.confirm-delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] text-red-600 hover:underline dark:text-red-400">
                                {{ __('sagawa::app.credentials.delete') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-admin::layouts>
