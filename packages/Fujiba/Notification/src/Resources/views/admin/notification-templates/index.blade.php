<x-admin::layouts>
    <x-slot:title>{{ __('fujiba_notification::app.page.title') }}</x-slot>

    @php
        $varLabel = fn (string $name): string => '{{' . $name . '}}';
        $existingButtons = $template?->buttons ?? [];
    @endphp

    @push('styles')
        <style>
            .fnotif-mode-bar { display: flex; gap: 8px; margin-bottom: 0; }
            .fnotif-mode-tab { padding: 8px 18px; border-radius: 6px 6px 0 0; font-weight: 500; font-size: 13px; border: 1px solid #e5e7eb; border-bottom: none; color: #6b7280; background: #f3f4f6; text-decoration: none; display: inline-block; }
            .fnotif-mode-tab.active { background: #fff; color: #2563eb; border-color: #d1d5db; }
            .fnotif-mode-tab:hover:not(.active) { color: #2563eb; }

            .fnotif-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0 8px 8px 8px; padding: 20px; }

            .fnotif-grid { display: grid; grid-template-columns: 240px 1fr; gap: 20px; align-items: start; }
            @media (max-width: 900px) { .fnotif-grid { grid-template-columns: 1fr; } }

            .fnotif-side { background: #f9fafb; border-radius: 8px; padding: 8px; }
            .fnotif-side-h { padding: 8px 12px 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; color: #6b7280; }
            .fnotif-side a { display: block; padding: 9px 12px; border-radius: 6px; color: #1f2937; font-size: 13px; text-decoration: none; }
            .fnotif-side a:hover { background: #eff6ff; }
            .fnotif-side a.active { background: #2563eb; color: #fff; font-weight: 500; }

            .fnotif-recipient-bar { display: flex; gap: 6px; margin-bottom: 12px; align-items: center; }
            .fnotif-recipient-bar-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-right: 4px; }
            .fnotif-recipient-chip { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #f3f4f6; color: #1f2937; text-decoration: none; }
            .fnotif-recipient-chip.cust-active { background: #2563eb; color: #fff; }
            .fnotif-recipient-chip.adm-active { background: #dc2626; color: #fff; }

            .fnotif-tabs { display: flex; gap: 4px; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; margin-bottom: 14px; }
            .fnotif-tabs a { padding: 9px 16px; font-size: 13px; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -1px; text-decoration: none; }
            .fnotif-tabs a:hover:not(.disabled):not(.active) { color: #2563eb; }
            .fnotif-tabs a.active { color: #2563eb; border-bottom-color: #2563eb; font-weight: 500; }
            .fnotif-tabs a.disabled { color: #c7c7cc; cursor: not-allowed; }
            .fnotif-soon { font-size: 9px; background: #f3f4f6; color: #86868b; padding: 1px 6px; border-radius: 4px; margin-left: 4px; }

            .fnotif-store-bar { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; align-items: center; }
            .fnotif-store-chip { padding: 5px 12px; border-radius: 6px; background: #f3f4f6; font-size: 12px; font-weight: 500; color: #1f2937; text-decoration: none; }
            .fnotif-store-chip.active { background: #4f46e5; color: #fff; }

            .fnotif-langs { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }
            .fnotif-lang { padding: 5px 14px; border-radius: 999px; background: #f3f4f6; font-size: 12px; font-weight: 500; color: #1f2937; text-decoration: none; }
            .fnotif-lang.active { background: #2563eb; color: #fff; }
            .fnotif-lang.has-template::before { content: '●'; color: #059669; font-size: 8px; margin-right: 4px; vertical-align: middle; }
            .fnotif-lang.active.has-template::before { color: #fff; }

            .fnotif-pills { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; font-size: 12px; color: #6b7280; flex-wrap: wrap; }
            .fnotif-pill { padding: 3px 8px; background: #f3f4f6; border-radius: 4px; font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 11px; }

            .fnotif-form-group { margin-bottom: 14px; }
            .fnotif-field-label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #374151; text-transform: uppercase; letter-spacing: .3px; }
            .fnotif-input, .fnotif-textarea { width: 100%; padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: inherit; background: #fff; box-sizing: border-box; }
            .fnotif-textarea { font-family: ui-monospace, 'SF Mono', Menlo, monospace; line-height: 1.6; resize: vertical; }
            .fnotif-input:focus, .fnotif-textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
            .fnotif-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            @media (max-width: 600px) { .fnotif-form-row { grid-template-columns: 1fr; } }

            .fnotif-toggle-wrap { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
            .fnotif-toggle-wrap input { display: none; }
            .fnotif-toggle-track { display: inline-block; width: 36px; height: 20px; background: #d1d5db; border-radius: 999px; position: relative; transition: background .15s; flex-shrink: 0; }
            .fnotif-toggle-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: transform .15s; }
            .fnotif-toggle-wrap input:checked + .fnotif-toggle-track { background: #059669; }
            .fnotif-toggle-wrap input:checked + .fnotif-toggle-track::after { transform: translateX(16px); }

            .fnotif-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
            .fnotif-chip { padding: 4px 10px; background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; border-radius: 6px; font-size: 12px; font-family: ui-monospace, 'SF Mono', Menlo, monospace; cursor: pointer; user-select: none; }
            .fnotif-chip:hover { background: #dbeafe; }

            .fnotif-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; padding-top: 18px; border-top: 1px solid #e5e7eb; }
            .fnotif-btn { display: inline-flex; align-items: center; padding: 9px 20px; border-radius: 6px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; }
            .fnotif-btn-primary { background: #2563eb; color: #fff; }
            .fnotif-btn-primary:hover { background: #1d4ed8; }
            .fnotif-btn-danger { background: transparent; color: #dc2626; border: 1.5px solid #dc2626; }
            .fnotif-btn-danger:hover { background: #fef2f2; }

            .fnotif-alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; }
            .fnotif-alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
            .fnotif-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

            .fnotif-btn-row { display: grid; grid-template-columns: 1fr 120px 2fr 32px; gap: 8px; margin-bottom: 8px; align-items: center; }
            .fnotif-btn-row input, .fnotif-btn-row select { padding: 7px 9px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; }
            .fnotif-btn-row-remove { width: 28px; height: 28px; border: none; background: #fee2e2; color: #dc2626; border-radius: 6px; cursor: pointer; font-size: 16px; line-height: 1; }
            .fnotif-btn-add { padding: 6px 14px; background: #eff6ff; border: 1px dashed #93c5fd; border-radius: 6px; color: #1e40af; font-size: 12px; cursor: pointer; }
            .fnotif-btn-add:hover { background: #dbeafe; }
            .fnotif-vars-hint { font-size: 11px; color: #6b7280; align-self: center; }
            @media (max-width: 600px) { .fnotif-btn-row { grid-template-columns: 1fr; } }

            /* Dark mode overrides — Bagisto toggles `html.dark` */
            html.dark .fnotif-mode-tab { background: #1f2937; border-color: #374151; color: #9ca3af; }
            html.dark .fnotif-mode-tab.active { background: #111827; color: #60a5fa; border-color: #374151; }
            html.dark .fnotif-mode-tab:hover:not(.active) { color: #60a5fa; }

            html.dark .fnotif-card { background: #111827; border-color: #374151; }

            html.dark .fnotif-side { background: #1f2937; }
            html.dark .fnotif-side-h { color: #9ca3af; }
            html.dark .fnotif-side a { color: #e5e7eb; }
            html.dark .fnotif-side a:hover { background: #374151; }
            html.dark .fnotif-side a.active { background: #2563eb; color: #fff; }

            html.dark .fnotif-recipient-bar-label { color: #9ca3af; }
            html.dark .fnotif-recipient-chip { background: #1f2937; color: #e5e7eb; }
            html.dark .fnotif-recipient-chip.cust-active { background: #2563eb; color: #fff; }
            html.dark .fnotif-recipient-chip.adm-active { background: #dc2626; color: #fff; }

            html.dark .fnotif-tabs { border-bottom-color: #374151; }
            html.dark .fnotif-tabs a { color: #9ca3af; }
            html.dark .fnotif-tabs a:hover:not(.disabled):not(.active) { color: #60a5fa; }
            html.dark .fnotif-tabs a.active { color: #60a5fa; border-bottom-color: #60a5fa; }
            html.dark .fnotif-tabs a.disabled { color: #4b5563; }
            html.dark .fnotif-soon { background: #1f2937; color: #9ca3af; }

            html.dark .fnotif-store-chip { background: #1f2937; color: #e5e7eb; }
            html.dark .fnotif-store-chip.active { background: #4f46e5; color: #fff; }

            html.dark .fnotif-lang { background: #1f2937; color: #e5e7eb; }
            html.dark .fnotif-lang.active { background: #2563eb; color: #fff; }

            html.dark .fnotif-pills { color: #9ca3af; }
            html.dark .fnotif-pill { background: #1f2937; color: #e5e7eb; }

            html.dark .fnotif-field-label { color: #d1d5db; }
            html.dark .fnotif-input,
            html.dark .fnotif-textarea { background: #111827; border-color: #374151; color: #e5e7eb; }
            html.dark .fnotif-input::placeholder,
            html.dark .fnotif-textarea::placeholder { color: #6b7280; }
            html.dark .fnotif-input:focus,
            html.dark .fnotif-textarea:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,.15); }

            html.dark .fnotif-toggle-track { background: #4b5563; }

            html.dark .fnotif-chip { background: #1e3a8a; color: #bfdbfe; border-color: #1e40af; }
            html.dark .fnotif-chip:hover { background: #1e40af; }

            html.dark .fnotif-actions { border-top-color: #374151; }

            html.dark .fnotif-alert-success { background: #064e3b; color: #a7f3d0; border-color: #065f46; }
            html.dark .fnotif-alert-error { background: #7f1d1d; color: #fecaca; border-color: #991b1b; }

            html.dark .fnotif-btn-row input,
            html.dark .fnotif-btn-row select { background: #111827; border-color: #374151; color: #e5e7eb; }
            html.dark .fnotif-btn-row-remove { background: #7f1d1d; color: #fecaca; }
            html.dark .fnotif-btn-add { background: #1e3a8a; border-color: #1e40af; color: #bfdbfe; }
            html.dark .fnotif-btn-add:hover { background: #1e40af; }
            html.dark .fnotif-vars-hint { color: #9ca3af; }
        </style>
    @endpush

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="py-1 text-xl font-bold text-gray-800 dark:text-white">
            {{ __('fujiba_notification::app.page.title') }}
        </p>
    </div>

    <div class="fnotif-mode-bar">
        <a href="{{ route('admin.fujiba.notifications.index') }}"
           class="fnotif-mode-tab {{ request()->get('view') !== 'recipients' ? 'active' : '' }}">{{ __('fujiba_notification::app.page.tab_templates') }}</a>
        <a href="{{ route('admin.fujiba.notifications.index', ['view' => 'recipients']) }}"
           class="fnotif-mode-tab">{{ __('fujiba_notification::app.page.tab_admin_alerts') }}</a>
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
        <div class="fnotif-grid">
            <aside class="fnotif-side">
                <div class="fnotif-side-h">{{ __('fujiba_notification::app.events') }}</div>
                @foreach ($eventLabels as $ev => $label)
                    <a href="{{ route('admin.fujiba.notifications.index', ['event' => $ev, 'channel' => $currentChannel, 'language' => $currentLanguage, 'channel_id' => $selectedChannelId, 'recipient_type' => $currentRecipientType]) }}"
                       class="{{ $ev === $currentEvent ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </aside>

            <section>
                <div class="fnotif-recipient-bar">
                    <span class="fnotif-recipient-bar-label">{{ __('fujiba_notification::app.recipient.label') }}</span>
                    <a href="{{ route('admin.fujiba.notifications.index', ['event' => $currentEvent, 'channel' => $currentChannel, 'language' => $currentLanguage, 'channel_id' => $selectedChannelId, 'recipient_type' => 'customer']) }}"
                       class="fnotif-recipient-chip {{ $currentRecipientType === 'customer' ? 'cust-active' : '' }}">{{ __('fujiba_notification::app.recipient.customer') }}</a>
                    <a href="{{ route('admin.fujiba.notifications.index', ['event' => $currentEvent, 'channel' => $currentChannel, 'language' => $currentLanguage, 'channel_id' => $selectedChannelId, 'recipient_type' => 'admin']) }}"
                       class="fnotif-recipient-chip {{ $currentRecipientType === 'admin' ? 'adm-active' : '' }}">{{ __('fujiba_notification::app.recipient.admin') }}</a>
                </div>

                <div class="fnotif-tabs">
                    @foreach ($activeChannelLabels as $ch => $chLabel)
                        <a href="{{ route('admin.fujiba.notifications.index', ['event' => $currentEvent, 'channel' => $ch, 'language' => $currentLanguage, 'channel_id' => $selectedChannelId, 'recipient_type' => $currentRecipientType]) }}"
                           class="{{ $ch === $currentChannel ? 'active' : '' }}">{{ $chLabel }}</a>
                    @endforeach
                    @foreach ($comingSoonChannelLabels as $ch => $chLabel)
                        <a class="disabled">{{ $chLabel }}<span class="fnotif-soon">{{ __('fujiba_notification::app.coming_soon') }}</span></a>
                    @endforeach
                </div>

                <div class="fnotif-store-bar">
                    <span class="fnotif-recipient-bar-label">{{ __('fujiba_notification::app.store.label') }}</span>
                    <a href="{{ route('admin.fujiba.notifications.index', ['event' => $currentEvent, 'channel' => $currentChannel, 'language' => $currentLanguage, 'channel_id' => 'default', 'recipient_type' => $currentRecipientType]) }}"
                       class="fnotif-store-chip {{ $selectedChannelId === null ? 'active' : '' }}">{{ __('fujiba_notification::app.store.default_all') }}</a>
                    @foreach ($channels as $bagistoChannel)
                        <a href="{{ route('admin.fujiba.notifications.index', ['event' => $currentEvent, 'channel' => $currentChannel, 'language' => $currentLanguage, 'channel_id' => $bagistoChannel->id, 'recipient_type' => $currentRecipientType]) }}"
                           class="fnotif-store-chip {{ $selectedChannelId === $bagistoChannel->id ? 'active' : '' }}">{{ $bagistoChannel->code }}</a>
                    @endforeach
                </div>

                <div class="fnotif-langs">
                    @foreach ($languages as $lang)
                        @php $hasTpl = in_array($lang, $existingLanguages, true); @endphp
                        <a href="{{ route('admin.fujiba.notifications.index', ['event' => $currentEvent, 'channel' => $currentChannel, 'language' => $lang, 'channel_id' => $selectedChannelId, 'recipient_type' => $currentRecipientType]) }}"
                           class="fnotif-lang {{ $lang === $currentLanguage ? 'active' : '' }} {{ $hasTpl ? 'has-template' : '' }}">{{ strtoupper($lang) }}</a>
                    @endforeach
                </div>

                <div class="fnotif-pills">
                    <span class="fnotif-pill">{{ $currentEvent }}</span>
                    <span class="fnotif-pill">{{ $currentChannel }}</span>
                    <span class="fnotif-pill">{{ $currentLanguage }}</span>
                    <span class="fnotif-pill">{{ __('fujiba_notification::app.pill.store') }} {{ $selectedChannelId === null ? __('fujiba_notification::app.store.default_all') : ($channels->firstWhere('id', $selectedChannelId)?->code ?? $selectedChannelId) }}</span>
                    <span class="fnotif-pill">{{ __('fujiba_notification::app.pill.recipient') }} {{ $currentRecipientType }}</span>
                    @if ($template)
                        <span>· {{ __('fujiba_notification::app.pill.last_updated') }} {{ $template->updated_at?->diffForHumans() }}</span>
                    @else
                        <span>· {{ __('fujiba_notification::app.pill.new_template') }}</span>
                    @endif
                </div>

                @if ($template)
                    <form method="POST"
                          action="{{ route('admin.fujiba.notifications.destroy', $template->id) }}"
                          id="tpl-delete-form"
                          onsubmit="return confirm('{{ __('fujiba_notification::app.confirm_delete') }}');"
                          style="display:none">
                        @csrf @method('DELETE')
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.fujiba.notifications.save') }}" id="tpl-form">
                    @csrf
                    <input type="hidden" name="event"                value="{{ $currentEvent }}">
                    <input type="hidden" name="notification_channel" value="{{ $currentChannel }}">
                    <input type="hidden" name="recipient_type"       value="{{ $currentRecipientType }}">
                    <input type="hidden" name="language"             value="{{ $currentLanguage }}">
                    <input type="hidden" name="channel_id"           value="{{ $selectedChannelId ?? '' }}">

                    <div class="fnotif-form-group">
                        <label class="fnotif-toggle-wrap">
                            <input type="checkbox" name="is_active" value="1" {{ ($template->is_active ?? true) ? 'checked' : '' }}>
                            <span class="fnotif-toggle-track"></span>
                            <span>{{ __('fujiba_notification::app.is_active') }}</span>
                        </label>
                    </div>

                    @if ($currentChannel === 'mail')
                        <div class="fnotif-form-row">
                            <div class="fnotif-form-group">
                                <label class="fnotif-field-label">{{ __('fujiba_notification::app.from_name') }}</label>
                                <input type="text" name="from_name" maxlength="100" class="fnotif-input"
                                       placeholder="{{ config('mail.from.name') }}"
                                       value="{{ old('from_name', $template->from_name ?? '') }}">
                            </div>
                            <div class="fnotif-form-group">
                                <label class="fnotif-field-label">{{ __('fujiba_notification::app.from_email') }}</label>
                                <input type="email" name="from_email" maxlength="100" class="fnotif-input"
                                       placeholder="{{ config('mail.from.address') }}"
                                       value="{{ old('from_email', $template->from_email ?? '') }}">
                            </div>
                        </div>

                        <div class="fnotif-form-group">
                            <label class="fnotif-field-label">{{ __('fujiba_notification::app.subject') }}</label>
                            <input type="text" name="subject" maxlength="255" id="field-subject" class="fnotif-input" v-pre
                                   value="{{ old('subject', $template->subject ?? '') }}">
                            <div class="fnotif-chips">
                                @foreach ($variables as $var)
                                    <span class="fnotif-chip" data-target="field-subject" data-var="{{ $var }}" v-pre>{{ $varLabel($var) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="fnotif-form-group">
                        <label class="fnotif-field-label">{{ __('fujiba_notification::app.body') }}</label>
                        <textarea name="body" rows="12" id="field-body" class="fnotif-textarea" required v-pre>{{ old('body', $template->body ?? '') }}</textarea>
                        <div class="fnotif-chips">
                            @foreach ($variables as $var)
                                <span class="fnotif-chip" data-target="field-body" data-var="{{ $var }}" v-pre>{{ $varLabel($var) }}</span>
                            @endforeach
                        </div>
                    </div>

                    @if ($currentChannel === 'messenger')
                        <div class="fnotif-form-group">
                            <label class="fnotif-field-label">{{ __('fujiba_notification::app.buttons.label_max') }}</label>
                            <div id="button-rows">
                                @forelse ($existingButtons as $i => $btn)
                                    <div class="fnotif-btn-row" data-row="{{ $i }}">
                                        <input type="text" name="buttons[{{ $i }}][text]"  placeholder="{{ __('fujiba_notification::app.buttons.btn_label') }}" maxlength="60" value="{{ $btn['text'] ?? '' }}">
                                        <select name="buttons[{{ $i }}][type]">
                                            <option value="url"      {{ ($btn['type'] ?? '') === 'url'      ? 'selected' : '' }}>{{ __('fujiba_notification::app.buttons.btn_url') }}</option>
                                            <option value="postback" {{ ($btn['type'] ?? '') === 'postback' ? 'selected' : '' }}>{{ __('fujiba_notification::app.buttons.btn_postback') }}</option>
                                            <option value="phone"    {{ ($btn['type'] ?? '') === 'phone'    ? 'selected' : '' }}>{{ __('fujiba_notification::app.buttons.btn_phone') }}</option>
                                        </select>
                                        <input type="text" name="buttons[{{ $i }}][value]" placeholder="{{ __('fujiba_notification::app.buttons.btn_value_url') }}" maxlength="500" value="{{ $btn['value'] ?? '' }}">
                                        <button type="button" class="fnotif-btn-row-remove" onclick="this.parentElement.remove()">×</button>
                                    </div>
                                @empty
                                @endforelse
                            </div>
                            <button type="button" id="add-button-row" class="fnotif-btn-add">{{ __('fujiba_notification::app.buttons.add_button') }}</button>
                            <div class="fnotif-chips" style="margin-top:8px;">
                                <span class="fnotif-vars-hint">{{ __('fujiba_notification::app.buttons.vars_for_button') }}</span>
                                @foreach ($variables as $var)
                                    <span class="fnotif-chip" data-target="last-button-value" data-var="{{ $var }}" v-pre>{{ $varLabel($var) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="fnotif-actions">
                        @if ($template)
                            <button type="submit" form="tpl-delete-form" class="fnotif-btn fnotif-btn-danger">{{ __('fujiba_notification::app.delete') }}</button>
                        @endif
                        <button type="submit" class="fnotif-btn fnotif-btn-primary">{{ __('fujiba_notification::app.save') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
        const FN_BTN_I18N = {
            url:        @json(__('fujiba_notification::app.buttons.btn_url')),
            postback:   @json(__('fujiba_notification::app.buttons.btn_postback')),
            phone:      @json(__('fujiba_notification::app.buttons.btn_phone')),
            label_ph:   @json(__('fujiba_notification::app.buttons.btn_label')),
            value_ph:   @json(__('fujiba_notification::app.buttons.btn_value_url')),
            max_3:      @json(__('fujiba_notification::app.buttons.max_3_alert')),
        };

        document.querySelectorAll('.fnotif-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var targetId = chip.dataset.target;
                var variable = '{' + '{' + chip.dataset.var + '}' + '}';
                var el;
                if (targetId === 'last-button-value') {
                    var inputs = document.querySelectorAll('input[name^="buttons["][name$="][value]"]');
                    el = inputs.length ? inputs[inputs.length - 1] : null;
                } else {
                    el = document.getElementById(targetId);
                }
                if (!el) return;
                var start = el.selectionStart, end = el.selectionEnd;
                el.value = el.value.substring(0, start) + variable + el.value.substring(end);
                el.focus();
                el.selectionStart = el.selectionEnd = start + variable.length;
            });
        });

        var addBtn = document.getElementById('add-button-row');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var rows = document.getElementById('button-rows');
                var existing = rows.querySelectorAll('.fnotif-btn-row').length;
                if (existing >= 3) {
                    alert(FN_BTN_I18N.max_3);
                    return;
                }
                var div = document.createElement('div');
                div.className = 'fnotif-btn-row';
                div.innerHTML =
                    '<input type="text" name="buttons[' + existing + '][text]"  placeholder="' + FN_BTN_I18N.label_ph + '" maxlength="60">' +
                    '<select name="buttons[' + existing + '][type]">' +
                      '<option value="url">' + FN_BTN_I18N.url + '</option>' +
                      '<option value="postback">' + FN_BTN_I18N.postback + '</option>' +
                      '<option value="phone">' + FN_BTN_I18N.phone + '</option>' +
                    '</select>' +
                    '<input type="text" name="buttons[' + existing + '][value]" placeholder="' + FN_BTN_I18N.value_ph + '" maxlength="500">' +
                    '<button type="button" class="fnotif-btn-row-remove" onclick="this.parentElement.remove()">×</button>';
                rows.appendChild(div);
            });
        }
    </script>
    @endpush

</x-admin::layouts>
