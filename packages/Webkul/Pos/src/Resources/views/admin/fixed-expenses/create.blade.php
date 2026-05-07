<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.fixed-expenses.create.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-6">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.fixed-expenses.create.title')
        </p>
        <a href="{{ route('admin.pos.fixed-expenses.index') }}" class="transparent-button">
            @lang('pos::app.admin.fixed-expenses.create.back')
        </a>
    </div>

    <form action="{{ route('admin.pos.fixed-expenses.store') }}" method="POST">
        @csrf
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-6 grid gap-4">

            {{-- Outlet --}}
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('pos::app.admin.fixed-expenses.create.outlet')
                </x-admin::form.control-group.label>
                <select name="outlet_id" class="custom-select w-full" required>
                    <option value="">-- Select Outlet --</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </select>
                <x-admin::form.control-group.error control-name="outlet_id" />
            </x-admin::form.control-group>

            {{-- Name --}}
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('pos::app.admin.fixed-expenses.create.name')
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                />
                <x-admin::form.control-group.error control-name="name" />
            </x-admin::form.control-group>

            {{-- Category --}}
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('pos::app.admin.fixed-expenses.create.category')
                </x-admin::form.control-group.label>
                <select name="category" class="custom-select w-full" required>
                    @foreach (['rent','electricity','gas','water','salary','insurance','lease','other'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                            @lang('pos::app.admin.fixed-expenses.categories.'.$cat)
                        </option>
                    @endforeach
                </select>
                <x-admin::form.control-group.error control-name="category" />
            </x-admin::form.control-group>

            {{-- Amount --}}
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('pos::app.admin.fixed-expenses.create.amount')
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="number"
                    name="amount"
                    step="0.01"
                    min="0"
                    :value="old('amount')"
                    required
                />
                <x-admin::form.control-group.error control-name="amount" />
            </x-admin::form.control-group>

            {{-- Billing Day --}}
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('pos::app.admin.fixed-expenses.create.billing-day')
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="number"
                    name="billing_day"
                    min="1"
                    max="31"
                    :value="old('billing_day', 1)"
                    required
                />
                <x-admin::form.control-group.error control-name="billing_day" />
            </x-admin::form.control-group>

            {{-- Notes --}}
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('pos::app.admin.fixed-expenses.create.notes')
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="textarea"
                    name="notes"
                    :value="old('notes')"
                />
                <x-admin::form.control-group.error control-name="notes" />
            </x-admin::form.control-group>

            {{-- Active --}}
            <x-admin::form.control-group>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', 1) ? 'checked' : '' }}
                        class="rounded"
                    >
                    <span class="text-gray-700 dark:text-gray-300 text-sm">
                        @lang('pos::app.admin.fixed-expenses.create.active')
                    </span>
                </label>
            </x-admin::form.control-group>

            <div class="flex gap-3 pt-4 border-t dark:border-gray-700">
                <button type="submit" class="primary-button">
                    @lang('pos::app.admin.fixed-expenses.create.save')
                </button>
            </div>
        </div>
    </form>
</x-admin::layouts>
