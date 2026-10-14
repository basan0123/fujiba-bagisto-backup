<x-admin::layouts>
    <x-slot:title>Auto Poster — Preview approval</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'    => 'Preview approval',
            'subtitle' => 'Content Engine v2 previews with fabric-gate metrics. Approve / Reject only records your decision — nothing is posted or queued.',
        ])

        @include('autoposter::admin._flash')

        <form method="GET" class="ap-card ap-rise" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <label for="dir"><strong>Preview run</strong></label>
            <select name="dir" id="dir" onchange="this.form.submit()">
                @foreach ($dirs as $d)
                    <option value="{{ $d }}" @selected($d === $dir)>{{ $d }}</option>
                @endforeach
            </select>
        </form>

        @if (empty($assets))
            <div class="ap-card ap-empty ap-rise">
                <h3>No gated previews</h3>
                <p>Run <code>php artisan tenants:run autoposter:v2-dryrun</code> to render previews.</p>
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
                @foreach ($assets as $a)
                    @php($s = $a['summary'])
                    <div class="ap-card ap-rise" style="display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                            <strong style="word-break:break-all">{{ $a['key'] }}</strong>
                            <span style="padding:2px 10px;border-radius:12px;color:#fff;background:{{ $a['passed'] ? '#15803d' : '#b91c1c' }}">
                                {{ $a['passed'] ? 'GATE PASS' : 'GATE FAIL' }}
                            </span>
                        </div>

                        @if ($a['delivery'])
                            @php($src = route('admin.autoposter.previews.media', [$dir, $a['delivery']]))
                            @if ($a['video'])
                                <video src="{{ $src }}" controls preload="metadata" style="width:100%;border-radius:8px;background:#000"></video>
                            @else
                                <a href="{{ $src }}" target="_blank"><img src="{{ $src }}" alt="{{ $a['key'] }}" style="width:100%;border-radius:8px"></a>
                            @endif
                        @else
                            <p style="color:#b91c1c">No delivery file (only derived after a gate pass).</p>
                        @endif

                        <table class="ap-table" style="font-size:13px">
                            <tr><td>Fabric regions checked</td><td>{{ $s['regions'] ?? '–' }}</td></tr>
                            <tr><td>ΔE00 mean / p95 (worst, σ1.5 blur)</td><td>{{ $s['worst_deltaE00_mean'] ?? '–' }} / {{ $s['worst_deltaE00_p95'] ?? '–' }} <small>(max 2.0 / 5.0)</small></td></tr>
                            <tr><td>SSIM (min)</td><td>{{ $s['min_ssim'] ?? '–' }} <small>(min 0.95)</small></td></tr>
                            <tr><td>ORB inlier ratio (min)</td><td>{{ $s['min_orb_inliers'] ?? '–' }} <small>(min 0.6)</small></td></tr>
                            <tr><td>Edge diff (max)</td><td>{{ $s['max_edge_diff'] ?? '–' }} <small>(max 0.15)</small></td></tr>
                            <tr><td>Delivery drift ΔE00</td><td>{{ $a['drift'] ?? '–' }}</td></tr>
                        </table>

                        @if (! empty($a['sbs']))
                            <div style="display:flex;flex-direction:column;gap:6px;margin:6px 0">
                                @foreach ($a['sbs'] as $sbs)
                                    @php($sbsSrc = route('admin.autoposter.previews.media', [$dir, $sbs]))
                                    <a href="{{ $sbsSrc }}" target="_blank" title="{{ $sbs }}"><img src="{{ $sbsSrc }}" alt="{{ $sbs }}" loading="lazy" style="width:100%;border:1px solid #ddd"></a>
                                    <span style="font-size:11px;color:#666">{{ $sbs }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($a['mask'])
                            <a href="{{ route('admin.autoposter.previews.media', [$dir, $a['mask']]) }}" target="_blank" style="font-size:13px">Show verified-fabric mask</a>
                        @endif

                        @foreach ($a['failed'] as $f)
                            <div style="color:#b91c1c;font-size:13px">✗ {{ $f['check'] }} {{ implode(', ', $f['fails'] ?? []) }} {{ $f['detail'] ?? '' }}</div>
                        @endforeach

                        <div style="font-size:13px;color:#555">Faces, kanji and motif meaning are not machine-checked — please look before approving.</div>

                        @if ($a['decision'])
                            <div style="font-size:13px">
                                Decision: <strong>{{ strtoupper($a['decision']['decision']) }}</strong>
                                by {{ $a['decision']['by'] ?? '?' }} · {{ $a['decision']['at'] }}
                                @if ($a['decision']['note']) — “{{ $a['decision']['note'] }}” @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.autoposter.previews.decide', [$dir, $a['key']]) }}" style="display:flex;flex-direction:column;gap:6px">
                            @csrf
                            <input type="text" name="note" maxlength="500" placeholder="Note (optional)" class="ap-input">
                            <div style="display:flex;gap:8px">
                                <button name="decision" value="approved" class="ap-btn ap-btn-primary" @disabled(! $a['passed'])>Approve</button>
                                <button name="decision" value="rejected" class="ap-btn">Reject</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin::layouts>
