<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.fixed-expenses.index.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-6">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.fixed-expenses.index.title')
        </p>
        <div class="flex gap-3">
            <button
                type="button"
                class="transparent-button"
                onclick="applyThisMonth()"
            >
                @lang('pos::app.admin.fixed-expenses.index.apply-month-btn')
            </button>
            <a href="{{ route('admin.pos.fixed-expenses.create') }}" class="primary-button">
                @lang('pos::app.admin.fixed-expenses.index.add-btn')
            </a>
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.pos.fixed-expenses.index')" />

    <script>
        async function applyThisMonth() {
            const month = new Date().toISOString().slice(0, 7); // YYYY-MM
            if (!confirm(`Apply fixed expenses for ${month}?`)) return;
            const resp = await fetch('{{ route('admin.pos.fixed-expenses.apply-month') }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body:    JSON.stringify({ month }),
            });
            const data = await resp.json();
            if (resp.ok) {
                alert(data.message ?? 'Applied');
            } else {
                alert(data.message ?? 'Error');
            }
        }
    </script>
</x-admin::layouts>
