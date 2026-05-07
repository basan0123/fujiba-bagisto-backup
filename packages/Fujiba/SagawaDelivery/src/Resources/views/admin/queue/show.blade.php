@extends('sagawa::admin.layout')
@section('title', '注文 #' . $shipment->order_id)

@section('content')
<div x-data="sagawaShipment({
    shipmentId: {{ $shipment->id }},
    initialStatus: '{{ $shipment->status }}',
    initialAddress: {{ Js::from($shipment->original_address ?? []) }},
    correctedAddress: {{ Js::from($shipment->corrected_address ?? $shipment->original_address ?? []) }},
    trackingNumber: {{ Js::from($shipment->tracking_number) }},
    pdfUrl: {{ Js::from($shipment->pdf_url) }},
    waybillIssuedAt: {{ Js::from($shipment->waybill_issued_at?->toIso8601String()) }},
    reissueWindowHours: {{ (int) config('sagawa.reissue_window_hours', 72) }},
})">

    {{-- Status badge --}}
    <div class="card">
        <div class="card-header">
            <span style="font-size:16px;font-weight:600;">注文 #{{ $shipment->order_id }}</span>
            <span class="badge" :class="'badge-' + status" x-text="status"></span>
        </div>
        <div class="meta">
            @if($shipment->delivery_date)
                配達日: {{ $shipment->delivery_date->format('Y-m-d') }}
                {{ $shipment->delivery_time ? '/ '.$shipment->delivery_time : '' }}
            @endif
        </div>
    </div>

    {{-- Address comparison panel --}}
    <div class="card" x-show="['pending_verification','address_verified','address_rejected'].includes(status)">
        <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;">住所チェック</h3>

        <div class="addr-compare">
            <div class="addr-col">
                <h4>元の住所</h4>
                <p x-text="(initialAddress.postcode || '') + ' ' + (initialAddress.prefecture || '') + (initialAddress.city || '') + (initialAddress.address || '') + ' ' + (initialAddress.address2 || '')"></p>
            </div>
            <div class="addr-col">
                <h4>修正住所</h4>
                <div class="form-group">
                    <label>郵便番号</label>
                    <input type="text" x-model="corrected.postcode" maxlength="10"
                           @blur="if(corrected.postcode.replace(/-/g,'').length===7) fillZip()">
                </div>
                <div class="form-group">
                    <label>都道府県</label>
                    <input type="text" x-model="corrected.prefecture" maxlength="10">
                </div>
                <div class="form-group">
                    <label>市区町村</label>
                    <input type="text" x-model="corrected.city" maxlength="100">
                </div>
                <div class="form-group">
                    <label>住所</label>
                    <input type="text" x-model="corrected.address" maxlength="500">
                </div>
                <div class="form-group">
                    <label>建物名等</label>
                    <input type="text" x-model="corrected.address2" maxlength="500">
                </div>
            </div>
        </div>

        {{-- Address check result indicator --}}
        <div style="display:flex;align-items:center;gap:8px;margin:12px 0;" x-show="checkResult !== null">
            <span class="result-dot" :class="checkResultColor"></span>
            <span x-text="checkResultLabel" style="font-size:13px;"></span>
        </div>

        {{-- W0-0001 candidate list --}}
        <template x-if="candidates.length > 0">
            <div style="margin:12px 0;">
                <p style="font-size:12px;color:#6b7280;margin-bottom:8px;">候補住所を選択:</p>
                <template x-for="(c, i) in candidates" :key="i">
                    <label style="display:flex;align-items:flex-start;gap:8px;padding:8px;border-radius:8px;cursor:pointer;margin-bottom:4px;"
                           :style="selectedCandidate === i ? 'background:#eff6ff' : ''">
                        <input type="radio" name="candidate" :value="i" x-model.number="selectedCandidate"
                               @change="applyCandidate(i)" style="margin-top:3px;">
                        <span x-text="c.address" style="font-size:13px;"></span>
                    </label>
                </template>
            </div>
        </template>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
            <button class="btn btn-primary btn-sm" @click="checkAddress" :disabled="loading"
                    x-show="status === 'pending_verification'">
                <span x-show="!loading">住所チェック</span>
                <span x-show="loading">確認中…</span>
            </button>
            <button class="btn btn-success" @click="approve" :disabled="loading"
                    x-show="['pending_verification','address_rejected'].includes(status)">
                承認して発行
            </button>
            <button class="btn btn-danger btn-sm" @click="showRejectModal = true"
                    x-show="['pending_verification','address_verified'].includes(status)">
                却下
            </button>
        </div>
    </div>

    {{-- Waybill pending --}}
    <div class="card" x-show="status === 'waybill_pending'" style="text-align:center;">
        <div class="badge badge-waybill_pending" style="margin-bottom:8px;">発行処理中…</div>
        <p class="meta">しばらくお待ちください。自動でPDFが生成されます。</p>
    </div>

    {{-- Waybill issued --}}
    <div class="card" x-show="status === 'waybill_issued'">
        <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;">送り状発行済</h3>
        <div class="tracking-box">
            <code x-text="trackingNum"></code>
            <button class="btn btn-sm btn-primary" @click="copyTracking" style="margin-left:auto;">コピー</button>
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
            <a :href="pdfLink" target="_blank" class="btn btn-primary btn-sm" x-show="pdfLink">PDF表示</a>
            <button class="btn btn-sm" style="background:#7c3aed;color:#fff;" @click="reissue" :disabled="loading || !canReissue"
                    x-show="trackingNum">
                再発行依頼
                <span class="countdown" x-show="reissueCountdown" x-text="'(' + reissueCountdown + ')'"></span>
            </button>
        </div>
    </div>

    {{-- Waybill failed --}}
    <div class="card" x-show="status === 'waybill_failed'">
        <h3 style="font-size:14px;font-weight:600;color:#dc2626;margin-bottom:8px;">送り状発行失敗</h3>
        <p class="meta">{{ $shipment->waybill_failed_reason ?? '不明なエラー' }}</p>
    </div>

    {{-- Reject modal --}}
    <template x-teleport="body">
        <div x-show="showRejectModal" style="position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:flex-end;justify-content:center;z-index:100;padding:16px;"
             @click.self="showRejectModal = false" x-transition>
            <div style="background:#fff;border-radius:16px 16px 0 0;width:100%;max-width:480px;padding:24px;">
                <h3 style="font-size:16px;font-weight:600;margin-bottom:16px;">却下理由</h3>
                <div class="form-group">
                    <textarea x-model="rejectReason" rows="3" placeholder="理由を入力してください"></textarea>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="btn btn-sm" style="background:#e5e5e5;" @click="showRejectModal = false">キャンセル</button>
                    <button class="btn btn-danger btn-sm" @click="reject" :disabled="!rejectReason.trim()">却下する</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Toast --}}
    <div x-show="toast" x-transition
         style="position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:#1d1d1f;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;z-index:200;"
         x-text="toast" @click="toast = ''"></div>
</div>

@push('scripts')
<script>
function sagawaShipment(opts) {
    return {
        shipmentId: opts.shipmentId,
        status: opts.initialStatus,
        initialAddress: opts.initialAddress || {},
        corrected: Object.assign({postcode:'',prefecture:'',city:'',address:'',address2:''}, opts.correctedAddress || opts.initialAddress || {}),
        trackingNum: opts.trackingNumber || '',
        pdfLink: opts.pdfUrl || '',
        waybillIssuedAt: opts.waybillIssuedAt ? new Date(opts.waybillIssuedAt) : null,
        reissueWindowHours: opts.reissueWindowHours || 72,

        loading: false,
        toast: '',
        showRejectModal: false,
        rejectReason: '',
        checkResult: null,
        candidates: [],
        selectedCandidate: null,

        get checkResultColor() {
            if (!this.checkResult) return '';
            const c = (this.checkResult.result_code || '').substring(0,2);
            if (c === 'S0') return 'green';
            if (c === 'W0') return 'yellow';
            return 'red';
        },
        get checkResultLabel() {
            if (!this.checkResult) return '';
            const c = (this.checkResult.result_code || '');
            if (c.startsWith('S0')) return '住所一致';
            if (c.startsWith('W0')) return '候補あり — 選択してください';
            return 'エラー: ' + c;
        },
        get canReissue() {
            if (!this.waybillIssuedAt) return true;
            const h = (Date.now() - this.waybillIssuedAt.getTime()) / 36e5;
            return h <= this.reissueWindowHours;
        },
        get reissueCountdown() {
            if (!this.waybillIssuedAt) return '';
            const left = this.reissueWindowHours - ((Date.now() - this.waybillIssuedAt.getTime()) / 36e5);
            if (left <= 0) return '期限切れ';
            return Math.floor(left) + '時間';
        },

        async api(url, body) {
            this.loading = true;
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: body ? JSON.stringify(body) : undefined,
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.error || 'Request failed');
                return d;
            } finally {
                this.loading = false;
            }
        },

        async checkAddress() {
            try {
                const d = await this.api(`/admin/sagawa/shipments/${this.shipmentId}/address-check`);
                this.notify('住所チェック開始');
                setTimeout(() => this.refresh(), 3000);
            } catch (e) { this.notify(e.message); }
        },

        async approve() {
            try {
                await this.api(`/admin/sagawa/shipments/${this.shipmentId}/verify`, {
                    corrected_address: this.corrected
                });
                this.status = 'waybill_pending';
                this.notify('承認しました — 送り状発行中');
            } catch (e) { this.notify(e.message); }
        },

        async reject() {
            try {
                await this.api(`/admin/sagawa/shipments/${this.shipmentId}/reject`, {
                    reason: this.rejectReason
                });
                this.status = 'address_rejected';
                this.showRejectModal = false;
                this.notify('却下しました');
            } catch (e) { this.notify(e.message); }
        },

        async reissue() {
            try {
                const d = await this.api(`/admin/sagawa/shipments/${this.shipmentId}/reissue`);
                this.status = 'reissue_requested';
                this.notify('再発行依頼を送信しました');
                setTimeout(() => this.refresh(), 5000);
            } catch (e) { this.notify(e.message); }
        },

        copyTracking() {
            navigator.clipboard.writeText(this.trackingNum);
            this.notify('コピーしました');
        },

        async fillZip() {
            const zip = this.corrected.postcode.replace(/-/g, '');
            if (zip.length !== 7) return;
            try {
                const r = await fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${zip}`);
                const d = await r.json();
                if (d.results && d.results[0]) {
                    const a = d.results[0];
                    this.corrected.prefecture = a.address1;
                    this.corrected.city = a.address2;
                    this.corrected.address = a.address3;
                }
            } catch {}
        },

        applyCandidate(i) {
            const c = this.candidates[i];
            if (!c) return;
            if (c.postcode)   this.corrected.postcode   = c.postcode;
            if (c.prefecture) this.corrected.prefecture = c.prefecture;
            if (c.city)       this.corrected.city       = c.city;
            if (c.address)    this.corrected.address    = c.address;
        },

        async refresh() {
            try {
                const r = await fetch(`/admin/sagawa/shipments/${this.shipmentId}`, {
                    headers: {'Accept': 'application/json'}
                });
                const d = await r.json();
                this.status = d.status;
                if (d.tracking_number) this.trackingNum = d.tracking_number;
                if (d.pdf_url)         this.pdfLink = d.pdf_url;
                if (d.address_check_result) {
                    this.checkResult = d.address_check_result;
                }
            } catch {}
        },

        notify(msg) {
            this.toast = msg;
            setTimeout(() => this.toast = '', 3000);
        }
    };
}
</script>
@endpush
@endsection
