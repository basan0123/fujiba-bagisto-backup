<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\Staff;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $query = Staff::query()->orderBy('staff_code');

        if ($request->filled('q')) {
            $q = '%' . $request->string('q') . '%';
            $query->where(function ($qq) use ($q) {
                $qq->where('staff_code', 'like', $q)
                   ->orWhere('name_ja', 'like', $q)
                   ->orWhere('name_kana', 'like', $q);
            });
        }
        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->integer('channel_id'));
        }
        if ($request->filled('active')) {
            $query->where('is_active', (bool) $request->integer('active'));
        }

        return view('accounting::admin.staff.index', [
            'staff'  => $query->paginate(50)->withQueryString(),
            'filters'=> $request->only(['q', 'channel_id', 'active']),
        ]);
    }

    public function create(): View
    {
        return view('accounting::admin.staff.edit', [
            'staff' => new Staff(['employment_type' => 'part_time', 'is_active' => true, 'withholding_class' => 'A']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateForm($request);

        $staff = Staff::create(array_merge($data, [
            'staff_code' => ($data['staff_code'] ?? null) ?: $this->nextStaffCode(),
            'qr_token'   => ($data['qr_token']   ?? null) ?: $this->generateQrToken(),
        ]));

        return redirect()
            ->route('admin.accounting.staff.edit', $staff->id)
            ->with('success', 'スタッフを登録しました。');
    }

    public function edit(int $id): View
    {
        return view('accounting::admin.staff.edit', [
            'staff' => Staff::findOrFail($id),
        ]);
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $staff = Staff::findOrFail($id);
        $data = $this->validateForm($request, $staff->id);

        $staff->fill($data)->save();

        return back()->with('success', '更新しました。');
    }

    public function destroy(int $id): RedirectResponse
    {
        $staff = Staff::findOrFail($id);
        // FK from timecards/payroll restricts hard-delete; flip is_active instead.
        $staff->update(['is_active' => false, 'termination_date' => now()->toDateString()]);

        return redirect()->route('admin.accounting.staff.index')->with('success', 'スタッフを無効化しました。');
    }

    public function qrPrint(int $id): View
    {
        $staff = Staff::findOrFail($id);
        return view('accounting::admin.staff.qr', [
            'staff'  => $staff,
            'svg'    => $this->qrSvg($staff->qr_token),
            'kioskUrl' => route('accounting.kiosk.index'),
        ]);
    }

    protected function validateForm(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'staff_code'      => ['nullable', 'string', 'max:16',
                                  Rule::unique('fujiba_staff', 'staff_code')->ignore($ignoreId)],
            'name_ja'         => ['required', 'string', 'max:128'],
            'name_kana'       => ['nullable', 'string', 'max:128'],
            'name_en'         => ['nullable', 'string', 'max:128'],
            'email'           => ['nullable', 'email', 'max:128'],
            'phone'           => ['nullable', 'string', 'max:32'],
            'qr_token'        => ['nullable', 'string', 'max:64',
                                  Rule::unique('fujiba_staff', 'qr_token')->ignore($ignoreId)],
            'channel_id'      => ['nullable', 'integer'],
            'employment_type' => ['required', Rule::in(['full_time','part_time','arubaito','contractor','officer'])],
            'hire_date'       => ['nullable', 'date'],
            'termination_date'=> ['nullable', 'date'],
            'withholding_class' => ['required', Rule::in(['A', 'B', 'C'])],
            'dependents_count'=> ['nullable', 'integer', 'min:0', 'max:99'],
            'hourly_rate'     => ['nullable', 'numeric', 'min:0'],
            'monthly_salary'  => ['nullable', 'numeric', 'min:0'],
            'enrolled_health_insurance'    => ['nullable', 'boolean'],
            'enrolled_pension'             => ['nullable', 'boolean'],
            'enrolled_employment_insurance'=> ['nullable', 'boolean'],
            'bank_name'           => ['nullable', 'string', 'max:64'],
            'bank_code'           => ['nullable', 'string', 'regex:/^\d{4}$/'],
            'bank_branch'         => ['nullable', 'string', 'max:64'],
            'bank_branch_code'    => ['nullable', 'string', 'regex:/^\d{3}$/'],
            'bank_account_type'   => ['nullable', 'string', 'max:16'],
            'bank_account_number' => ['nullable', 'string', 'max:32'],
            'bank_account_holder' => ['nullable', 'string', 'max:128'],
            'bank_account_holder_kana' => ['nullable', 'string', 'max:64'],
            // Kiosk PIN: 4 digits, optional. Empty/null = leave existing pin_hash alone.
            // The model mutator setPinAttribute() bcrypts on save.
            'pin'                 => ['nullable', 'string', 'regex:/^\d{4}$/'],
            'is_active'           => ['nullable', 'boolean'],
            'notes'               => ['nullable', 'string'],
        ]);
    }

    protected function nextStaffCode(): string
    {
        $last = Staff::query()->orderByDesc('id')->value('staff_code');
        $n = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $n = ((int) $m[1]) + 1;
        }
        return 'S' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    protected function generateQrToken(): string
    {
        do {
            $t = (string) Str::ulid();
        } while (Staff::where('qr_token', $t)->exists());
        return $t;
    }

    /**
     * Tiny inline SVG QR using a public-CDN service URL approach is undesirable
     * (no internet on kiosk). Render via the bacon/qr package if installed,
     * otherwise emit a placeholder div so the print page is usable for ops to
     * scan via 3rd-party generator. Sprint 5 can install the package.
     */
    protected function qrSvg(string $token): string
    {
        if (class_exists(\BaconQrCode\Writer::class)) {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(280),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd(),
            );
            return (new \BaconQrCode\Writer($renderer))->writeString($token);
        }
        // Fallback: human-readable token + cssgrid mock; operator can paste
        // into any QR generator. Avoids a hard dep on bacon/qr.
        return '<div style="padding:24px;border:2px dashed #999;font-family:monospace;font-size:14px;text-align:center">'
             . htmlspecialchars($token, ENT_QUOTES) . '</div>';
    }
}
