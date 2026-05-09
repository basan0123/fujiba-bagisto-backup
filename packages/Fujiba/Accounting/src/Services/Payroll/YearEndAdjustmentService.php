<?php

namespace Fujiba\Accounting\Services\Payroll;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;

/**
 * 年末調整 (Year-End Adjustment).
 *
 * Sprint 6 Phase 3B — basic flow:
 *   1. Sum the year's gross_pay / income_tax_withheld for the staff.
 *   2. Compute true annual income tax using:
 *      - 給与所得控除 (employment income deduction)
 *      - 基礎控除 ¥480,000
 *      - 扶養控除 ¥380,000 / 人
 *      - 社保控除 (sum of HI + Pension + EI for the year)
 *      - 生命保険控除 + 地震保険控除 (operator-entered, see staff metadata)
 *   3. Difference (withheld − true tax) → adjustment to 12月 slip.
 *      Positive = refund (deduction reversal), negative = additional withholding.
 *
 * Staff metadata: insurance amounts live in `staff.notes` as a JSON object
 * (no schema change). Reader/writer helpers are below.
 *
 *   {
 *     "ye_2026": {
 *        "life_insurance":      55000,
 *        "earthquake_insurance": 18000,
 *        "small_enterprise":      0,
 *        "medical_expenses":      0
 *     }
 *   }
 *
 * Returns a proposal — caller decides whether to apply via slip update.
 */
class YearEndAdjustmentService
{
    public const BASIC_DEDUCTION  = 480000;     // 基礎控除
    public const DEPENDENT_AMOUNT = 380000;     // 1人あたり 扶養控除
    public const SPOUSE_AMOUNT    = 380000;     // 配偶者控除 (配偶者がいる場合)

    /**
     * @return array{
     *   year: int,
     *   staff_id: int,
     *   annual_gross: int,
     *   annual_employment_deduction: int,
     *   annual_si_deduction: int,
     *   annual_basic_deduction: int,
     *   annual_dependent_deduction: int,
     *   annual_insurance_deduction: int,
     *   annual_taxable_income: int,
     *   annual_true_tax: int,
     *   annual_withheld: int,
     *   adjustment: int,           // positive = refund, negative = additional withholding
     *   target_slip_id: int|null,  // 12月 monthly slip to apply to
     * }
     */
    public function propose(Staff $staff, int $year): array
    {
        $slips = PayrollSlip::where('staff_id', $staff->id)
            ->where('year_month', 'like', "{$year}-%")
            ->get();

        $gross         = (int) $slips->sum('gross_pay');
        $withheld      = (int) $slips->sum('income_tax');
        $siTotal       = (int) ($slips->sum('health_insurance') + $slips->sum('pension') + $slips->sum('employment_insurance'));

        $employmentDeduction = $this->employmentIncomeDeduction($gross);
        $insurance = $this->insuranceMeta($staff, $year);
        $insuranceDeduction = $this->insuranceDeduction($insurance);

        $deps = (int) ($staff->dependents_count ?? 0);
        $depDeduction = $deps * self::DEPENDENT_AMOUNT;

        $taxable = max(0, $gross - $employmentDeduction - self::BASIC_DEDUCTION - $depDeduction - $siTotal - $insuranceDeduction);
        $trueTax = $this->annualIncomeTax($taxable);
        $adjustment = $withheld - $trueTax;

        $targetSlip = $slips->where('year_month', "{$year}-12")->where('is_bonus', false)->first();

        return [
            'year'                          => $year,
            'staff_id'                      => $staff->id,
            'annual_gross'                  => $gross,
            'annual_employment_deduction'   => $employmentDeduction,
            'annual_si_deduction'           => $siTotal,
            'annual_basic_deduction'        => self::BASIC_DEDUCTION,
            'annual_dependent_deduction'    => $depDeduction,
            'annual_insurance_deduction'    => $insuranceDeduction,
            'annual_taxable_income'         => $taxable,
            'annual_true_tax'               => $trueTax,
            'annual_withheld'               => $withheld,
            'adjustment'                    => $adjustment,
            'target_slip_id'                => $targetSlip?->id,
        ];
    }

    /**
     * Apply the adjustment to the 12月 monthly slip:
     * - Positive adjustment (refund): subtract from `income_tax`, recompute net.
     * - Negative adjustment: add to `income_tax`.
     * Stores the proposal blob in `calc_meta.year_end_adjustment` for audit.
     */
    public function applyToDecember(array $proposal): ?PayrollSlip
    {
        if (! $proposal['target_slip_id']) {
            return null;
        }
        $slip = PayrollSlip::find($proposal['target_slip_id']);
        if (! $slip || $slip->status === 'finalized') {
            return null;
        }

        $newIncomeTax = max(0, (int) $slip->income_tax - $proposal['adjustment']);
        $newTotalDed  = (int) $slip->total_deduction - (int) $slip->income_tax + $newIncomeTax;
        $newNet       = max(0, (int) $slip->gross_pay - $newTotalDed);

        $meta = is_array($slip->calc_meta) ? $slip->calc_meta : [];
        $meta['year_end_adjustment'] = $proposal;

        $slip->fill([
            'income_tax'      => $newIncomeTax,
            'total_deduction' => $newTotalDed,
            'net_pay'         => $newNet,
            'calc_meta'       => $meta,
        ])->save();

        return $slip->fresh();
    }

    /**
     * 給与所得控除 (令和2年改正): bracket lookup.
     */
    public function employmentIncomeDeduction(int $gross): int
    {
        return match (true) {
            $gross <= 1625000  => 550000,
            $gross <= 1800000  => (int) floor($gross * 0.40 - 100000),
            $gross <= 3600000  => (int) floor($gross * 0.30 + 80000),
            $gross <= 6600000  => (int) floor($gross * 0.20 + 440000),
            $gross <= 8500000  => (int) floor($gross * 0.10 + 1100000),
            default            => 1950000,   // 上限
        };
    }

    /**
     * 所得税の年税額 — 累進税率 (令和8年版簡易).
     */
    public function annualIncomeTax(int $taxable): int
    {
        $rate = match (true) {
            $taxable <=    1949000 => ['rate' => 5,  'deduction' => 0],
            $taxable <=    3299000 => ['rate' => 10, 'deduction' => 97500],
            $taxable <=    6949000 => ['rate' => 20, 'deduction' => 427500],
            $taxable <=    8999000 => ['rate' => 23, 'deduction' => 636000],
            $taxable <=   17999000 => ['rate' => 33, 'deduction' => 1536000],
            $taxable <=   39999000 => ['rate' => 40, 'deduction' => 2796000],
            default                => ['rate' => 45, 'deduction' => 4796000],
        };
        $tax = max(0, (int) floor($taxable * $rate['rate'] / 100.0) - $rate['deduction']);
        // 復興特別所得税 +2.1%
        return (int) floor($tax * 1.021);
    }

    /**
     * 生命保険料控除 + 地震保険料控除 — 簡易計算.
     * Cap: 生保 ¥120k / 地震 ¥50k.
     *
     * @param  array<string,int>  $meta
     */
    public function insuranceDeduction(array $meta): int
    {
        $life = (int) ($meta['life_insurance'] ?? 0);
        $eq   = (int) ($meta['earthquake_insurance'] ?? 0);

        // Life: scaled brackets (旧制度簡易); cap ¥120k
        $lifeDeduction = match (true) {
            $life <= 25000  => $life,
            $life <= 50000  => (int) floor($life / 2 + 12500),
            $life <= 100000 => (int) floor($life / 4 + 25000),
            default         => 50000,
        };
        $lifeDeduction = min(120000, $lifeDeduction);

        $eqDeduction = min(50000, $eq);

        return $lifeDeduction + $eqDeduction;
    }

    /**
     * Read insurance metadata from staff.notes (JSON).
     * Returns empty array if missing/malformed.
     */
    public function insuranceMeta(Staff $staff, int $year): array
    {
        $notes = trim((string) $staff->notes);
        if ($notes === '') {
            return [];
        }
        $data = json_decode($notes, true);
        if (! is_array($data)) {
            return [];
        }
        return $data["ye_{$year}"] ?? [];
    }

    /**
     * Persist insurance metadata into staff.notes JSON for the given year.
     *
     * @param  array<string,int>  $values
     */
    public function setInsuranceMeta(Staff $staff, int $year, array $values): void
    {
        $existing = json_decode((string) $staff->notes, true);
        if (! is_array($existing)) {
            $existing = [];
        }
        $existing["ye_{$year}"] = array_intersect_key(
            $values,
            array_flip(['life_insurance', 'earthquake_insurance', 'small_enterprise', 'medical_expenses']),
        );
        $staff->notes = json_encode($existing, JSON_UNESCAPED_UNICODE);
        $staff->save();
    }
}
