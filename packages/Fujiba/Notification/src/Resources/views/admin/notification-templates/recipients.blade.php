<x-admin::layouts>
    <x-slot:title>{{ __('fujiba_notification::app.recipients.page_title') }}</x-slot>

    @push('styles')
        <style>
            .fnotif-mode-bar { display: flex; gap: 8px; margin-bottom: 0; }
            .fnotif-mode-tab { padding: 8px 18px; border-radius: 6px 6px 0 0; font-weight: 500; font-size: 13px; border: 1px solid #e5e7eb; border-bottom: none; color: #6b7280; background: #f3f4f6; text-decoration: none; display: inline-block; }
            .fnotif-mode-tab.active { background: #fff; color: #2563eb; border-color: #d1d5db; }
            .fnotif-mode-tab:hover:not(.active) { color: #2563eb; }

            .fnotif-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0 8px 8px 8px; padding: 20px; }

            .fnotif-alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; }
            .fnotif-alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
            .fnotif-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

            .fnotif-add-form { background: #f9fafb; padding: 14px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb; }
            .fnotif-add-grid { display: grid; grid-template-columns: 1.2fr 1.4fr 1.4fr 1.4fr auto; gap: 8px; align-items: end; }
            @media (max-width: 800px) { .fnotif-add-grid { grid-template-columns: 1fr; } }
            .fnotif-field-label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
            .fnotif-input, .fnotif-select { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
            .fnotif-input.mono { font-family: ui-monospace, 'SF Mono', Menlo, monospace; }

            .fnotif-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .fnotif-table th { text-align: left; padding: 10px; font-size: 11px; text-transform: uppercase; color: #6b7280; background: #f3f4f6; }
            .fnotif-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
            .fnotif-table .mono { font-family: ui-monospace, 'SF Mono', Menlo, monospace; }
            .fnotif-empty { padding: 32px; text-align: center; color: #6b7280; font-size: 13px; }

            .fnotif-pill-btn { background: #059669; color: #fff; padding: 3px 12px; border: none; border-radius: 999px; font-size: 11px; font-weight: 500; cursor: pointer; }
            .fnotif-pill-btn.inactive { background: #9ca3af; }

            .fnotif-btn { display: inline-flex; align-items: center; padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; }
            .fnotif-btn-primary { background: #2563eb; color: #fff; }
            .fnotif-btn-primary:hover { background: #1d4ed8; }
            .fnotif-btn-danger { background: transparent; color: #dc2626; border: 1.5px solid #dc2626; padding: 4px 10px; font-size: 11px; }
            .fnotif-btn-danger:hover { background: #fef2f2; }

            /* Dark mode overrides — Bagisto toggles `html.dark` */
            html.dark .fnotif-mode-tab { background: #1f2937; border-color: #374151; color: #9ca3af; }
            html.dark .fnotif-mode-tab.active { background: #111827; color: #60a5fa; border-color: #374151; }
            html.dark .fnotif-mode-tab:hover:not(.active) { color: #60a5fa; }

            html.dark .fnotif-card { background: #111827; border-color: #374151; }

            html.dark .fnotif-alert-success { background: #064e3b; color: #a7f3d0; border-color: #065f46; }
            html.dark .fnotif-alert-error { background: #7f1d1d; color: #fecaca; border-color: #991b1b; }

            html.dark .fnotif-add-form { background: #1f2937; border-color: #374151; }
            html.dark .fnotif-field-label { color: #9ca3af; }
            html.dark .fnotif-input,
            html.dark .fnotif-select { background: #111827; border-color: #374151; color: #e5e7eb; }
            html.dark .fnotif-input::placeholder { color: #6b7280; }

            html.dark .fnotif-table th { background: #1f2937; color: #9ca3af; }
            html.dark .fnotif-table td { border-bottom-color: #374151; color: #e5e7eb; }
            html.dark .fnotif-empty { color: #9ca3af; }

            html.dark .fnotif-pill-btn.inactive { background: #4b5563; }
        </style>
    @endpush

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="py-1 text-xl font-bold text-gray-800 dark:text-white">
            {{ __('fujiba_notification::app.recipients.page_title') }}
        </p>
    </div>

    <div class="fnotif-mode-bar">
        <a href="{{ route('admin.fujiba.notifications.index') }}" class="fnotif-mode-tab">{{ __('fujiba_notification::app.page.tab_templates') }}</a>
        <a href="{{ route('admin.fujiba.notifications.index', ['view' => 'recipients']) }}" class="fnotif-mode-tab active">{{ __('fujiba_notification::app.page.tab_admin_alerts') }}</a>
    </div>

    @if (session('success'))
        <div class="fnotif-alert fnotif-alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="fnotif-alert fnotif-alert-error">
            @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
        </div>
    @endif

    <div class="fnotif-card">
        <h2 class="text-base font-semibold mb-1 text-gray-800 dark:text-white">{{ __('fujiba_notification::app.recipients.heading') }}</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            {{ __('fujiba_notification::app.recipients.description') }}
        </p>

        <form method="POST" action="{{ route('admin.fujiba.notifications.recipients.store') }}" class="fnotif-add-form">
            @csrf
            <div class="fnotif-add-grid">
                <div>
                    <label class="fnotif-field-label">{{ __('fujiba_notification::app.recipients.channel') }}</label>
                    <select name="channel_id" class="fnotif-select">
                        <option value="">{{ __('fujiba_notification::app.store.default_all') }}</option>
                        @foreach ($channels as $c)
                            <option value="{{ $c->id }}">{{ $c->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fnotif-field-label">{{ __('fujiba_notification::app.recipients.page_id') }}</label>
                    <input type="text" name="page_id" required maxlength="100" placeholder="{{ __('fujiba_notification::app.recipients.page_id_ph') }}" class="fnotif-input mono">
                </div>
                <div>
                    <label class="fnotif-field-label">{{ __('fujiba_notification::app.recipients.psid') }}</label>
                    <input type="text" name="psid" required maxlength="64" placeholder="{{ __('fujiba_notification::app.recipients.psid_ph') }}" class="fnotif-input mono">
                </div>
                <div>
                    <label class="fnotif-field-label">{{ __('fujiba_notification::app.recipients.recipient_label') }}</label>
                    <input type="text" name="label" maxlength="100" placeholder="{{ __('fujiba_notification::app.recipients.recipient_label_ph') }}" class="fnotif-input">
                </div>
                <button type="submit" class="fnotif-btn fnotif-btn-primary">{{ __('fujiba_notification::app.recipients.add') }}</button>
            </div>
        </form>

        <table class="fnotif-table">
            <thead>
                <tr>
                    <th>{{ __('fujiba_notification::app.recipients.channel') }}</th>
                    <th>{{ __('fujiba_notification::app.recipients.page_id') }}</th>
                    <th>{{ __('fujiba_notification::app.recipients.psid') }}</th>
                    <th>{{ __('fujiba_notification::app.recipients.col_label') }}</th>
                    <th style="text-align:center;">{{ __('fujiba_notification::app.recipients.col_active') }}</th>
                    <th style="text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recipients as $r)
                    @php
                        $channelCode = $r->channel_id
                            ? optional($channels->firstWhere('id', $r->channel_id))->code ?? "ch={$r->channel_id}"
                            : __('fujiba_notification::app.store.default_all');
                    @endphp
                    <tr>
                        <td>{{ $channelCode }}</td>
                        <td class="mono">{{ $r->page_id }}</td>
                        <td class="mono">{{ $r->psid }}</td>
                        <td>{{ $r->label ?: '—' }}</td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('admin.fujiba.notifications.recipients.toggle', $r->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="fnotif-pill-btn {{ $r->is_active ? '' : 'inactive' }}">
                                    {{ $r->is_active ? __('fujiba_notification::app.recipients.pill_active') : __('fujiba_notification::app.recipients.pill_inactive') }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.fujiba.notifications.recipients.destroy', $r->id) }}"
                                  onsubmit="return confirm('{{ __('fujiba_notification::app.recipients.confirm_remove') }}');" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="fnotif-btn fnotif-btn-danger">{{ __('fujiba_notification::app.recipients.remove') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="fnotif-empty">
                            {{ __('fujiba_notification::app.recipients.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-admin::layouts>
