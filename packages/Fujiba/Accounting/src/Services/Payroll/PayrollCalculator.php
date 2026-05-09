<?php

namespace Fujiba\Accounting\Services\Payroll;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\TaxRate;
use Fujiba\Accounting\Models\Timecard;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Compute a PayrollSlip from approved timecards.
 *
 * Rules (Japanese 労基法 minimum):
 *   - Regular hourly wage     = hourly_rate
 *   - Overtime premium        = +25% (so paid at 1.25x for the overtime portion)
 *   - Night premium           = +25%  (22:00–05:00) layered on top of regular/overtime
 *   - Holiday premium         = +35% (法定休日)
 *   - Monthly-salary staff use `monthly_salary` as base; their overtime is still
 *     hourly-derived using a notional hourly = monthly_salary / 160 (industry default)
 *
 * Deductions use seeded rates from `fujiba_tax_rates`:
 *   health_insurance_employee, pension_employee, employment_insurance_employee → flat %
 *   withholding_a → bracket ladder applied to taxable income (gross − commute − SI deductions)
 *
 * 住民税 (resident tax) is operator-entered per staff for now (special-collection
 * notice arrives from the city in May/June each year). Sprint 5 will add a
 * settings table for it.
 *
 * Money: integers JPY, rounded down (yen has no fractional unit).
 */
class PayrollCalculator
{
    /**
     * Build a draft slip for $staff in $yearMonth ("YYYY-MM").
     * Saves with status=draft if not already finalized; returns the model.
     */
    public function calculate(Staff $staff, string $yearMonth, ?CarbonInterface $payDate = null): PayrollSlip
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            throw new \InvalidArgumentException("Invalid year_month: {$yearMonth}");
        }

        $existing = PayrollSlip::where('staff_id', $staff->id)
            ->where('year_month', $yearMonth)
            ->where('is_bonus', false)   // monthly path; bonus uses calculateBonus()
            ->first();
        if ($existing && $existing->status !== 'draft') {
            return $existing;   // already finalized; don't clobber
        }

        $start = Carbon::parse("{$yearMonth}-01")->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // Pull approved timecards for the window.
        $cards = Timecard::where('staff_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'approved')
            ->get();

        $regularMin  = (int) $cards->sum('work_minutes');
        $overtimeMin = (int) $cards->sum('overtime_minutes');
        $nightMin    = (int) $cards->sum('night_minutes');
        $holidayMin  = (int) $cards->sum('holiday_minutes');

        // Hourly rate fallback for monthly-salary staff
        $hourly = (float) ($staff->hourly_rate ?? 0);
        if ($hourly <= 0 && (float) ($staff->monthly_salary ?? 0) > 0) {
            $hourly = round(((float) $staff->monthly_salary) / 160.0);
        }

        $basePay = (float) ($staff->monthly_salary ?? 0) > 0
            ? (int) round((float) $staff->monthly_salary)
            : (int) floor($hourly * $regularMin / 60.0);

        $overtimePay = (int) floor($hourly * 1.25 * $overtimeMin / 60.0);
        $nightPay    = (int) floor($hourly * 0.25 * $nightMin / 60.0);
        $holidayPay  = (int) floor($hourly * 1.35 * $holidayMin / 60.0);

        $commute      = (int) round((float) ($existing->commute_allowance ?? 0));
        $otherAllow   = (int) round((float) ($existing->other_allowance ?? 0));

        $gross = $basePay + $overtimePay + $nightPay + $holidayPay + $commute + $otherAllow;

        // Deductions
        $hi = $pen = $ei = 0;
        if ($staff->enrolled_health_insurance) {
            $hi = $this->pct($gross - $commute, $this->rate('health_insurance_employee'));
        }
        if ($staff->enrolled_pension) {
            $pen = $this->pct($gross - $commute, $this->rate('pension_employee'));
        }
        if ($staff->enrolled_employment_insurance) {
            $ei = $this->pct($gross, $this->rate('employment_insurance_employee'));
        }

        $taxable = max(0, $gross - $commute - $hi - $pen - $ei);
        $workingDays = $cards->whereStrict('status', 'approved')->count();
        $incomeTax = $this->withholding(
            taxable:    $taxable,
            class:      $staff->withholding_class ?? 'A',
            dependents: $staff->dependents_count ?? 0,
            workingDays: $workingDays,
        );

        // Resident tax priority:
        //   1. Operator-edited value already on the slip (highest priority)
        //   2. Imported value from `fujiba_settings.resident_tax:{staff}:{ym}` (Phase 3C)
        //   3. ¥0 (default — operator must enter manually)
        $residentTax = (int) round((float) ($existing->resident_tax ?? 0));
        if ($residentTax === 0) {
            $residentTax = app(\Fujiba\Accounting\Services\Payroll\ResidentTaxImporter::class)
                ->get($staff->id, $yearMonth);
        }
        $otherDeduction = (int) round((float) ($existing->other_deduction ?? 0));

        $totalDeduction = $hi + $pen + $ei + $incomeTax + $residentTax + $otherDeduction;
        $netPay = max(0, $gross - $totalDeduction);

        $payload = [
            'staff_id'              => $staff->id,
            'year_month'            => $yearMonth,
            'is_bonus'              => false,
            'pay_date'              => $payDate?->toDateString(),
            'channel_id'            => $staff->channel_id,
            'base_pay'              => $basePay,
            'overtime_pay'          => $overtimePay,
            'night_pay'             => $nightPay,
            'holiday_pay'           => $holidayPay,
            'commute_allowance'     => $commute,
            'other_allowance'       => $otherAllow,
            'gross_pay'             => $gross,
            'health_insurance'      => $hi,
            'pension'               => $pen,
            'employment_insurance'  => $ei,
            'income_tax'            => $incomeTax,
            'resident_tax'          => $residentTax,
            'other_deduction'       => $otherDeduction,
            'total_deduction'       => $totalDeduction,
            'net_pay'               => $netPay,
            'regular_minutes'       => $regularMin,
            'overtime_minutes'      => $overtimeMin,
            'night_minutes'         => $nightMin,
            'holiday_minutes'       => $holidayMin,
            'status'                => 'draft',
            'calc_meta'             => [
                'hourly_rate_used'   => $hourly,
                'withholding_class'  => $staff->withholding_class ?? 'A',
                'rate_codes'         => [
                    'health_insurance_employee',
                    'pension_employee',
                    'employment_insurance_employee',
                    'withholding_' . strtolower($staff->withholding_class ?? 'A'),
                ],
                'computed_at'        => now()->toIso8601String(),
            ],
        ];

        if ($existing) {
            $existing->fill($payload)->save();
            return $existing->fresh();
        }
        return PayrollSlip::create($payload);
    }

    /**
     * 賞与計算 — Sprint 6 Phase 3A.
     *
     * 賞与の所得税は「賞与に対する源泉徴収税額の算出率の表」を使う:
     *   1. 前月の課税給与 (gross − 社保) を bonus bracket で照合 → rate%
     *   2. 賞与額 × rate% = 賞与所得税
     *
     * 社保は通常の率を使う (健保 / 厚年 / 雇用保険) — 完全な賞与標準月額
     * 上限制度は Sprint 7 で精緻化。常用給与と同じ率で十分実用的。
     *
     * @param  int  $bonusAmount  賞与の総支給額 (yen)
     * @param  int|null  $prevTaxable  前月の課税給与 (gross - SI). null時はDBから前月slip取得
     */
    public function calculateBonus(Staff $staff, string $yearMonth, int $bonusAmount, ?int $prevTaxable = null, ?CarbonInterface $payDate = null): PayrollSlip
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            throw new \InvalidArgumentException("Invalid year_month: {$yearMonth}");
        }
        if ($bonusAmount <= 0) {
            throw new \InvalidArgumentException('Bonus amount must be > 0');
        }

        $existing = PayrollSlip::where('staff_id', $staff->id)
            ->where('year_month', $yearMonth)
            ->where('is_bonus', true)
            ->first();
        if ($existing && $existing->status !== 'draft') {
            return $existing;
        }

        if ($prevTaxable === null) {
            $prevYm = Carbon::parse("{$yearMonth}-01")->subMonth()->format('Y-m');
            $prevSlip = PayrollSlip::where('staff_id', $staff->id)
                ->where('year_month', $prevYm)
                ->where('is_bonus', false)
                ->first();
            $prevTaxable = $prevSlip
                ? max(0, (int) $prevSlip->gross_pay
                       - (int) $prevSlip->commute_allowance
                       - (int) $prevSlip->health_insurance
                       - (int) $prevSlip->pension
                       - (int) $prevSlip->employment_insurance)
                : 0;
        }

        $bonusRate = $this->bonusRate($prevTaxable, $staff->dependents_count ?? 0);

        $hi = $pen = $ei = 0;
        if ($staff->enrolled_health_insurance) {
            $hi = $this->pct($bonusAmount, $this->rate('health_insurance_employee'));
        }
        if ($staff->enrolled_pension) {
            $pen = $this->pct($bonusAmount, $this->rate('pension_employee'));
        }
        if ($staff->enrolled_employment_insurance) {
            $ei = $this->pct($bonusAmount, $this->rate('employment_insurance_employee'));
        }

        $taxable = max(0, $bonusAmount - $hi - $pen - $ei);
        $incomeTax = (int) floor($taxable * $bonusRate / 100.0);

        $totalDeduction = $hi + $pen + $ei + $incomeTax;
        $net = max(0, $bonusAmount - $totalDeduction);

        $payload = [
            'staff_id'             => $staff->id,
            'year_month'           => $yearMonth,
            'is_bonus'             => true,
            'prev_taxable'         => $prevTaxable,
            'pay_date'             => $payDate?->toDateString(),
            'channel_id'           => $staff->channel_id,
            'base_pay'             => $bonusAmount,
            'gross_pay'            => $bonusAmount,
            'health_insurance'     => $hi,
            'pension'              => $pen,
            'employment_insurance' => $ei,
            'income_tax'           => $incomeTax,
            'resident_tax'         => 0,
            'total_deduction'      => $totalDeduction,
            'net_pay'              => $net,
            'status'               => 'draft',
            'calc_meta'            => [
                'is_bonus'           => true,
                'prev_taxable'       => $prevTaxable,
                'bonus_rate_pct'     => $bonusRate,
                'rate_codes'         => ['bonus_withholding_rate'],
                'computed_at'        => now()->toIso8601String(),
            ],
        ];

        if ($existing) {
            $existing->fill($payload)->save();
            return $existing->fresh();
        }
        return PayrollSlip::create($payload);
    }

    /**
     * Look up bonus withholding rate from the seeded bracket table.
     * Dependents shift: 扶養 1人 ¥31,667 引き下げ.
     */
    protected function bonusRate(int $prevTaxable, int $dependents): float
    {
        $brackets = TaxRate::where('code', 'bonus_withholding_rate')->value('brackets');
        if (! is_array($brackets) || empty($brackets)) {
            return 0.0;
        }
        $shift = $dependents * 31667;
        $effective = max(0, $prevTaxable - $shift);

        foreach ($brackets as $b) {
            $min = (int) ($b['prev_taxable_min'] ?? 0);
            $max = $b['prev_taxable_max'] ?? null;
            if ($effective >= $min && ($max === null || $effective <= (int) $max)) {
                return (float) ($b['rate'] ?? 0);
            }
        }
        return 0.0;
    }

    protected function pct(int $base, float $ratePct): int
    {
        if ($base <= 0 || $ratePct <= 0) {
            return 0;
        }
        return (int) floor($base * $ratePct / 100.0);
    }

    protected function rate(string $code): float
    {
        return (float) (TaxRate::where('code', $code)->value('rate') ?? 0.0);
    }

    /**
     * 源泉徴収税 calculation, dispatching by 甲/乙/丙 class.
     *
     * - A (甲欄, monthly): 国税庁 月額表 bracket → rate − deduction.
     *                     dependents_count shifts brackets ¥31,667/人 (¥380k/年 ÷ 12).
     * - B (乙欄, monthly secondary): flat 10.21% (10% income tax + 0.21% 復興税).
     * - C (丙欄, daily): convert taxable to daily amount via workingDays,
     *                    apply bracket, multiply back. ¥9,300/日 まで非課税.
     */
    protected function withholding(int $taxable, string $class = 'A', int $dependents = 0, int $workingDays = 0): int
    {
        $code = match (strtoupper($class)) {
            'B' => 'withholding_b',
            'C' => 'withholding_c',
            default => 'withholding_a',
        };

        $rate = TaxRate::where('code', $code)->first();
        if (! $rate) {
            return 0;
        }

        // Class B is the simplest path: flat rate with no brackets.
        if ($code === 'withholding_b') {
            return (int) floor($taxable * (float) $rate->rate / 100.0);
        }

        $brackets = $rate->brackets;
        if (! is_array($brackets) || empty($brackets)) {
            return 0;
        }

        // Class C: divide by working days, apply daily bracket, multiply back.
        if ($code === 'withholding_c') {
            $days = max(1, $workingDays);
            $perDay = (int) floor($taxable / $days);
            foreach ($brackets as $b) {
                $min = (int) ($b['taxable_min'] ?? 0);
                $max = $b['taxable_max'] ?? null;
                if ($perDay >= $min && ($max === null || $perDay <= (int) $max)) {
                    $r = (float) ($b['rate'] ?? 0);
                    $deduction = (int) ($b['deduction'] ?? 0);
                    $perDayTax = max(0, (int) floor($perDay * $r / 100.0) - $deduction);
                    return $perDayTax * $days;
                }
            }
            return 0;
        }

        // Class A: monthly bracket with dependent shift.
        $shift = $dependents * 31667;
        $effective = max(0, $taxable - $shift);

        foreach ($brackets as $b) {
            $min = (int) ($b['taxable_min'] ?? 0);
            $max = $b['taxable_max'] ?? null;
            if ($effective >= $min && ($max === null || $effective <= (int) $max)) {
                $r = (float) ($b['rate'] ?? 0);
                $deduction = (int) ($b['deduction'] ?? 0);
                $tax = (int) floor($effective * $r / 100.0) - $deduction;
                return max(0, $tax);
            }
        }
        return 0;
    }
}
