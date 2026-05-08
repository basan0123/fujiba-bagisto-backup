<x-admin::layouts>
    <x-slot:title>{{ $staff->exists ? $staff->name_ja : __('accounting::app.staff.create') }}</x-slot>

    @php $isNew = ! $staff->exists; @endphp

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ $isNew ? __('accounting::app.staff.create') : $staff->name_ja }}
        </p>
        <a href="{{ route('admin.accounting.staff.index') }}"
           class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">
            ← {{ __('accounting::app.common.back') }}
        </a>
    </div>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isNew ? route('admin.accounting.staff.store') : route('admin.accounting.staff.update', $staff->id) }}"
          class="mt-4 grid gap-4 md:grid-cols-2">
        @csrf
        @if (! $isNew) @method('PUT') @endif

        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">基本</p>
            <div class="grid gap-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.staff_code') }}</label>
                        <input type="text" name="staff_code" value="{{ old('staff_code', $staff->staff_code) }}" placeholder="自動生成"
                               class="mt-1 h-9 w-full rounded-md border px-3 font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.qr_token') }}</label>
                        <input type="text" name="qr_token" value="{{ old('qr_token', $staff->qr_token) }}" placeholder="自動生成"
                               class="mt-1 h-9 w-full rounded-md border px-3 font-mono text-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.name_ja') }} *</label>
                    <input type="text" name="name_ja" required value="{{ old('name_ja', $staff->name_ja) }}"
                           class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.name_kana') }}</label>
                        <input type="text" name="name_kana" value="{{ old('name_kana', $staff->name_kana) }}"
                               class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Email</label>
                        <input type="email" name="email" value="{{ old('email', $staff->email) }}"
                               class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.employment_type') }}</label>
                        <select name="employment_type" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                            @foreach (['full_time','part_time','arubaito','contractor','officer'] as $et)
                                <option value="{{ $et }}" @selected($staff->employment_type === $et)>{{ __('accounting::app.staff.employment_' . $et) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.channel') }}</label>
                        <select name="channel_id" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">—</option>
                            @foreach (config('accounting.channels', []) as $cid => $code)
                                <option value="{{ $cid }}" @selected((int) $staff->channel_id === (int) $cid)>{{ $cid }} - {{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.hourly_rate') }}</label>
                        <input type="number" name="hourly_rate" step="1" min="0" value="{{ old('hourly_rate', (int) $staff->hourly_rate) }}"
                               class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.monthly_salary') }}</label>
                        <input type="number" name="monthly_salary" step="1" min="0" value="{{ old('monthly_salary', (int) $staff->monthly_salary) }}"
                               class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.staff.hire_date') }}</label>
                        <input type="date" name="hire_date" value="{{ optional($staff->hire_date)->toDateString() }}"
                               class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">扶養人数</label>
                        <input type="number" name="dependents_count" min="0" max="99" value="{{ old('dependents_count', $staff->dependents_count) }}"
                               class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('accounting::app.staff.social_insurance') }}</p>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="enrolled_health_insurance" value="0"/>
                <input type="checkbox" name="enrolled_health_insurance" value="1" @checked($staff->enrolled_health_insurance)/>
                {{ __('accounting::app.staff.enrolled_health') }}
            </label>
            <label class="flex items-center gap-2 text-sm mt-2">
                <input type="hidden" name="enrolled_pension" value="0"/>
                <input type="checkbox" name="enrolled_pension" value="1" @checked($staff->enrolled_pension)/>
                {{ __('accounting::app.staff.enrolled_pension') }}
            </label>
            <label class="flex items-center gap-2 text-sm mt-2">
                <input type="hidden" name="enrolled_employment_insurance" value="0"/>
                <input type="checkbox" name="enrolled_employment_insurance" value="1" @checked($staff->enrolled_employment_insurance)/>
                {{ __('accounting::app.staff.enrolled_employment') }}
            </label>

            <div class="mt-4">
                <label class="text-xs font-medium text-gray-600">源泉徴収区分</label>
                <select name="withholding_class" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    @foreach (['A' => '甲', 'B' => '乙', 'C' => '丙'] as $val => $label)
                        <option value="{{ $val }}" @selected($staff->withholding_class === $val)>{{ $val }} ({{ $label }})</option>
                    @endforeach
                </select>
            </div>

            <p class="mt-6 mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('accounting::app.staff.bank_info') }}</p>
            <div class="grid gap-2">
                <input type="text" name="bank_name" value="{{ old('bank_name', $staff->bank_name) }}" placeholder="{{ __('accounting::app.staff.bank_name') }}"
                       class="h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                <input type="text" name="bank_branch" value="{{ old('bank_branch', $staff->bank_branch) }}" placeholder="{{ __('accounting::app.staff.bank_branch') }}"
                       class="h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                <div class="grid grid-cols-2 gap-2">
                    <select name="bank_account_type" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">—</option>
                        <option value="普通" @selected($staff->bank_account_type === '普通')>普通</option>
                        <option value="当座" @selected($staff->bank_account_type === '当座')>当座</option>
                    </select>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $staff->bank_account_number) }}" placeholder="{{ __('accounting::app.staff.bank_account_number') }}"
                           class="h-9 rounded-md border px-3 font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                </div>
                <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $staff->bank_account_holder) }}" placeholder="{{ __('accounting::app.staff.bank_account_holder') }}"
                       class="h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0"/>
                <input type="checkbox" name="is_active" value="1" @checked($staff->is_active)/>
                {{ __('accounting::app.staff.is_active') }}
            </label>
        </div>

        <div class="md:col-span-2 flex justify-between">
            @if (! $isNew)
                <form method="POST" action="{{ route('admin.accounting.staff.destroy', $staff->id) }}"
                      onsubmit="return confirm('Deactivate this staff member?');">
                    @csrf @method('DELETE')
                    <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                        無効化
                    </button>
                </form>
            @else
                <span></span>
            @endif
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                {{ __('accounting::app.common.save') }}
            </button>
        </div>
    </form>
</x-admin::layouts>
