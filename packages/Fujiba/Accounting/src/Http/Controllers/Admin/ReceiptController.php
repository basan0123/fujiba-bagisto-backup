<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Ocr\ReceiptOcrInterface;
use Fujiba\Accounting\Services\ReceiptJournalBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $query = Receipt::query()
            ->orderByDesc('id')
            ->with('account');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('vendor')) {
            $query->where('vendor_name', 'like', '%' . $request->string('vendor') . '%');
        }
        if ($request->filled('from')) {
            $query->whereDate('receipt_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('receipt_date', '<=', $request->date('to'));
        }

        return view('accounting::admin.receipts.index', [
            'receipts' => $query->paginate(50)->withQueryString(),
            'filters'  => [
                'status' => $request->input('status'),
                'vendor' => $request->input('vendor'),
                'from'   => $request->input('from'),
                'to'     => $request->input('to'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('accounting::admin.receipts.create', [
            'expenseAccounts' => $this->expenseAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('accounting.receipts.max_size_kb', 10240);
        $allowed = (array) config('accounting.receipts.allowed_mimes', ['jpg', 'jpeg', 'png', 'pdf']);

        $data = $request->validate([
            'file'           => ['required', 'file', 'max:' . $maxKb, 'mimes:' . implode(',', $allowed)],
            'channel_id'     => ['nullable', 'integer'],
            'vendor_name'    => ['nullable', 'string', 'max:128'],
            'receipt_date'   => ['nullable', 'date'],
            'total_amount'   => ['nullable', 'numeric', 'min:0'],
            'tax_category'   => ['nullable', Rule::in(['standard', 'reduced', 'non_taxable', 'exempt', 'out_of_scope'])],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'notes'          => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $disk = config('accounting.receipts.storage_disk', 'local');
        $root = trim((string) config('accounting.receipts.storage_root', 'accounting/receipts'), '/');
        $relativeDir = sprintf('%s/%s/%s', $root, now()->format('Y'), now()->format('m'));
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $name = (string) Str::uuid() . '.' . $ext;

        Storage::disk($disk)->putFileAs($relativeDir, $file, $name);
        $absolutePath = Storage::disk($disk)->path($relativeDir . '/' . $name);

        $sha = hash_file('sha256', $absolutePath) ?: null;

        $receipt = Receipt::create([
            'channel_id'        => $data['channel_id'] ?? null,
            'receipt_date'      => $data['receipt_date'] ?? null,
            'vendor_name'       => $data['vendor_name'] ?? null,
            'total_amount'      => $data['total_amount'] ?? 0,
            'tax_amount'        => 0,
            'tax_category'      => $data['tax_category'] ?? 'standard',
            'payment_method'    => $data['payment_method'] ?? null,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'      => $relativeDir . '/' . $name,
            'mime_type'         => $file->getMimeType(),
            'file_size_bytes'   => $file->getSize(),
            'file_sha256'       => $sha,
            'status'            => 'uploaded',
            'uploaded_by'       => auth('admin')->id() ?? auth()->id(),
            'notes'             => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.accounting.receipts.show', $receipt->id)
            ->with('success', '領収書をアップロードしました。');
    }

    public function show(int $id): View
    {
        $receipt = Receipt::with('account', 'journalEntry')->findOrFail($id);

        return view('accounting::admin.receipts.show', [
            'receipt'         => $receipt,
            'expenseAccounts' => $this->expenseAccounts(),
            'fileUrl'         => $this->signedFileUrl($receipt),
        ]);
    }

    public function runOcr(int $id, ReceiptOcrInterface $ocr): RedirectResponse
    {
        $receipt = Receipt::findOrFail($id);
        if ($receipt->status !== 'uploaded') {
            return back()->with('error', 'OCRはアップロード直後にのみ実行できます。');
        }

        $disk = config('accounting.receipts.storage_disk', 'local');
        $absolutePath = Storage::disk($disk)->path($receipt->storage_path);

        try {
            $result = $ocr->extract($absolutePath);
        } catch (\Throwable $e) {
            return back()->with('error', 'OCRに失敗しました: ' . $e->getMessage());
        }

        $receipt->fill([
            'vendor_name'     => $receipt->vendor_name ?: $result['vendor_name'],
            'vendor_t_number' => $receipt->vendor_t_number ?: $result['vendor_t_number'],
            'receipt_date'    => $receipt->receipt_date ?: $result['receipt_date'],
            'total_amount'    => $receipt->total_amount ?: ($result['total_amount'] ?? 0),
            'tax_amount'      => $receipt->tax_amount ?: ($result['tax_amount'] ?? 0),
            'payment_method'  => $receipt->payment_method ?: $result['payment_method'],
            'ocr_raw_json'    => $result['raw_json'] ?? [],
            'status'          => 'ready_for_review',
        ])->save();

        return redirect()
            ->route('admin.accounting.receipts.show', $id)
            ->with('success', 'OCRが完了しました。内容を確認してください。');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $receipt = Receipt::findOrFail($id);
        if ($receipt->status === 'confirmed') {
            return back()->with('error', '確認済みの領収書は編集できません。');
        }

        $data = $request->validate([
            'vendor_name'    => ['nullable', 'string', 'max:128'],
            'vendor_t_number'=> ['nullable', 'regex:/^T\d{13}$/'],
            'receipt_date'   => ['nullable', 'date'],
            'total_amount'   => ['nullable', 'numeric', 'min:0'],
            'tax_amount'     => ['nullable', 'numeric', 'min:0'],
            'tax_category'   => ['nullable', Rule::in(['standard', 'reduced', 'non_taxable', 'exempt', 'out_of_scope'])],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'account_id'     => ['nullable', 'integer', 'exists:fujiba_accounts,id'],
            'notes'          => ['nullable', 'string'],
        ]);

        $receipt->fill($data)->save();

        return back()->with('success', '内容を更新しました。');
    }

    public function confirm(int $id, ReceiptJournalBuilder $builder, JournalPoster $poster): RedirectResponse
    {
        $receipt = Receipt::findOrFail($id);
        if ($receipt->status === 'confirmed') {
            return back()->with('error', '既に確認済みです。');
        }
        if (! $receipt->account_id) {
            return back()->with('error', '勘定科目を選択してください。');
        }
        if (! $receipt->total_amount || (float) $receipt->total_amount <= 0) {
            return back()->with('error', '金額を入力してください。');
        }

        $payload = $builder->build($receipt);
        $entry = $poster->post($payload);

        $receipt->fill([
            'journal_entry_id' => $entry->id,
            'status'           => 'confirmed',
            'confirmed_by'     => auth('admin')->id() ?? auth()->id(),
            'confirmed_at'     => now(),
        ])->save();

        return redirect()
            ->route('admin.accounting.receipts.show', $id)
            ->with('success', "仕訳 {$entry->entry_no} を作成しました。");
    }

    public function reject(int $id): RedirectResponse
    {
        $receipt = Receipt::findOrFail($id);
        if ($receipt->status === 'confirmed') {
            return back()->with('error', '確認済みの領収書は却下できません。');
        }
        $receipt->fill(['status' => 'rejected'])->save();

        return back()->with('success', '却下しました。');
    }

    public function destroy(int $id): RedirectResponse
    {
        $receipt = Receipt::findOrFail($id);
        if (! in_array($receipt->status, ['uploaded', 'rejected'], true)) {
            return back()->with('error', '確認済みの領収書は削除できません。');
        }

        if ($receipt->storage_path) {
            $disk = config('accounting.receipts.storage_disk', 'local');
            try {
                Storage::disk($disk)->delete($receipt->storage_path);
            } catch (\Throwable $e) {
                // log but proceed — DB row removal is the source of truth
                report($e);
            }
        }
        $receipt->delete();

        return redirect()->route('admin.accounting.receipts.index')->with('success', '削除しました。');
    }

    public function file(int $id)
    {
        $receipt = Receipt::findOrFail($id);
        $disk = config('accounting.receipts.storage_disk', 'local');

        abort_unless($receipt->storage_path && Storage::disk($disk)->exists($receipt->storage_path), 404);

        return response()->file(Storage::disk($disk)->path($receipt->storage_path), [
            'Content-Type' => $receipt->mime_type ?: 'application/octet-stream',
        ]);
    }

    protected function signedFileUrl(Receipt $receipt): string
    {
        return route('admin.accounting.receipts.file', $receipt->id);
    }

    protected function expenseAccounts()
    {
        return Account::query()
            ->whereIn('type', ['expense'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name_ja', 'sub_type']);
    }
}
