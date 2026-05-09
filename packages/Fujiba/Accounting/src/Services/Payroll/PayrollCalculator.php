<?php

namespace Fujiba\Accounting\Services\Payroll;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\TaxRate;
use Fujiba\Accounting\Models\Timecard;
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
    public function calculate(Staff $staff, string $yearMonth, ?Carbon $payDate = null): PayrollSlip
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            throw new \InvalidArgumentException("Invalid year_month: {$yearMonth}");
        }

        $existing = PayrollSlip::where('staff_id', $staff->id)
            ->where('year_month', $yearMonth)
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

        $residentTax    = (int) round((float) ($existing->resident_tax ?? 0));     // operator-entered
        $otherDeduction = (int) round((float) ($existing->other_deduction ?? 0));

        $totalDeduction = $hi + $pen + $ei + $incomeTax + $residentTax + $otherDeduction;
        $netPay = max(0, $gross - $totalDeduction);

        $payload = [
            'staff_id'              => $staff->id,
            'year_month'            => $yearMonth,
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
