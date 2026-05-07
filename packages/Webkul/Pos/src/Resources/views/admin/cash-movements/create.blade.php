<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.cash-movements.create.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-6">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.cash-movements.create.title')
        </p>
        <a href="{{ route('admin.pos.cash-movements.index') }}" class="transparent-button">
            @lang('pos::app.admin.cash-movements.create.back')
        </a>
    </div>

    <form action="{{ route('admin.pos.cash-movements.store') }}" method="POST">
        @csrf
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-6 grid gap-4">

            {{-- Outlet --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('pos::app.admin.cash-movements.create.outlet')
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
            </div>

            {{-- Type --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('pos::app.admin.cash-movements.create.type')
                    </x-admin::form.control-group.label>
                    <select name="type" class="custom-select w-full" required>
                        <option value="income"  {{ old('type') === 'income'  ? 'selected' : '' }}>@lang('pos::app.admin.cash-movements.create.income')</option>
                        <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>@lang('pos::app.admin.cash-movements.create.expense')</option>
                    </select>
                    <x-admin::form.control-group.error control-name="type" />
                </x-admin::form.control-group>
            </div>

            {{-- Category --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('pos::app.admin.cash-movements.create.category')
                    </x-admin::form.control-group.label>
                    <select name="category" class="custom-select w-full" required>
                        @foreach (['rent','electricity','gas','salary','supplies','misc','sales_other'] as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                @lang('pos::app.admin.cash-movements.categories.'.$cat)
                            </option>
                        @endforeach
                    </select>
                    <x-admin::form.control-group.error control-name="category" />
                </x-admin::form.control-group>
            </div>

            {{-- Description --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('pos::app.admin.cash-movements.create.description')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        name="description"
                        :value="old('description')"
                        required
                    />
                    <x-admin::form.control-group.error control-name="description" />
                </x-admin::form.control-group>
            </div>

            {{-- Amount --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('pos::app.admin.cash-movements.create.amount')
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
            </div>

            {{-- Date --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('pos::app.admin.cash-movements.create.date')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="date"
                        name="movement_date"
                        :value="old('movement_date', date('Y-m-d'))"
                        required
                    />
                    <x-admin::form.control-group.error control-name="movement_date" />
                </x-admin::form.control-group>
            </div>

            {{-- Reference --}}
            <div>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('pos::app.admin.cash-movements.create.reference')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        name="reference_number"
                        :value="old('reference_number')"
                    />
                    <x-admin::form.control-group.error control-name="reference_number" />
                </x-admin::form.control-group>
            </div>

            <div class="flex gap-3 pt-4 border-t dark:border-gray-700">
                <button type="submit" class="primary-button">
                    @lang('pos::app.admin.cash-movements.create.save')
                </button>
            </div>
        </div>
    </form>
</x-admin::layouts>
