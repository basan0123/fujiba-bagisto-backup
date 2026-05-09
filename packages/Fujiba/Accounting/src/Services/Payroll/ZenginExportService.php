<?php

namespace Fujiba\Accounting\Services\Payroll;

use Illuminate\Support\Collection;

/**
 * 全銀協 給与振込ファイル (Zengin format) generator.
 *
 * Spec: 120 byte 固定長 / Shift_JIS / CRLF 行末. 4 record types in order:
 *   1 — Header     (data_kind = 21 給与振込)
 *   2 — Data       (one per recipient)
 *   8 — Trailer    (totals)
 *   9 — End        (file footer)
 *
 * The output is `text/plain; charset=Shift_JIS`. Each line ends with CRLF.
 *
 * カナ: 半角カナのみ使用可。`mb_convert_kana()` で全角→半角に正規化する。
 *
 * The build() method takes a slip Collection and a sender array (the
 * employer's bank info). Slips with missing bank info are excluded and
 * surfaced in `errors` so the operator can fix them up.
 */
class ZenginExportService
{
    /** 給与振込 data_kind */
    public const DATA_KIND_PAYROLL = '21';

    /** 種別コード = 21 (給与) */
    public const CATEGORY = '21';

    /**
     * @param  iterable<\Fujiba\Accounting\Models\PayrollSlip>  $slips
     * @param  array{
     *   sender_code: string,        // 委託者コード (10桁)
     *   sender_name: string,        // 委託者カナ名 (40桁)
     *   transfer_date: string,      // 振込指定日 MMDD (4桁)
     *   bank_code: string,          // 仕向銀行コード (4桁)
     *   bank_name: string,          // 仕向銀行名カナ (15桁)
     *   branch_code: string,        // 仕向支店コード (3桁)
     *   branch_name: string,        // 仕向支店名カナ (15桁)
     *   account_type: string,       // 預金種目 1-4 (1桁)
     *   account_no: string,         // 口座番号 (7桁)
     * }  $sender
     *
     * @return array{data:string, included:int, errors:array<int,string>, totals:array{count:int,amount:int}}
     */
    public function build(iterable $slips, array $sender): array
    {
        $records = [];
        $records[] = $this->headerRecord($sender);

        $errors = [];
        $included = 0;
        $totalAmount = 0;
        $sequence = 0;

        foreach ($slips as $slip) {
            $staff = $slip->staff;
            if (! $staff) {
                $errors[$slip->id ?? 0] = "Slip {$slip->id} has no staff relation";
                continue;
            }

            $missing = $this->missingBankFields($staff);
            if ($missing) {
                $errors[$staff->id] = "Staff {$staff->staff_code}: missing " . implode(',', $missing);
                continue;
            }

            $amount = (int) round((float) $slip->net_pay);
            if ($amount <= 0) {
                $errors[$staff->id] = "Staff {$staff->staff_code}: net_pay={$amount}, skipped";
                continue;
            }

            $sequence++;
            $records[] = $this->dataRecord($staff, $amount, $sequence);
            $included++;
            $totalAmount += $amount;
        }

        $records[] = $this->trailerRecord($included, $totalAmount);
        $records[] = $this->endRecord();

        // Each record is already in SJIS-bytes (kanaPad encodes per-field;
        // numeric/space are ASCII). Just append CRLF and concat.
        $payload = implode('', array_map(fn (string $r) => $r . "\r\n", $records));

        return [
            'data'     => $payload,
            'included' => $included,
            'errors'   => $errors,
            'totals'   => ['count' => $included, 'amount' => $totalAmount],
        ];
    }

    // ───── record builders (each returns exactly 120 chars in UTF-8 pre-encode) ─────

    protected function headerRecord(array $sender): string
    {
        $fields  = '1';                                              // 1: header
        $fields .= self::DATA_KIND_PAYROLL;                          // 2: 21 給与振込
        $fields .= '0';                                              // 3: コード区分 0=JIS
        $fields .= $this->numeric($sender['sender_code'] ?? '', 10); // 4: 委託者コード
        $fields .= $this->kanaPad($sender['sender_name'] ?? '', 40); // 5: 委託者名 (カナ40桁)
        $fields .= $this->numeric($sender['transfer_date'] ?? '', 4); // 6: 振込指定日 MMDD
        $fields .= $this->numeric($sender['bank_code'] ?? '', 4);    // 7: 仕向銀行コード
        $fields .= $this->kanaPad($sender['bank_name'] ?? '', 15);   // 8: 仕向銀行名
        $fields .= $this->numeric($sender['branch_code'] ?? '', 3);  // 9: 仕向支店コード
        $fields .= $this->kanaPad($sender['branch_name'] ?? '', 15); // 10: 仕向支店名
        $fields .= $this->numeric($sender['account_type'] ?? '1', 1); // 11: 預金種目
        $fields .= $this->numeric($sender['account_no'] ?? '', 7);   // 12: 口座番号
        $fields .= str_repeat(' ', 17);                              // 13: ダミー
        return $this->pad120($fields);
    }

    protected function dataRecord(\Fujiba\Accounting\Models\Staff $staff, int $amount, int $sequence): string
    {
        $fields  = '2';                                                          // 1: 2 = data
        $fields .= $this->numeric($staff->bank_code, 4);                         // 2: 受取人銀行コード
        $fields .= $this->kanaPad($staff->bank_name ?? '', 15);                  // 3: 受取人銀行名
        $fields .= $this->numeric($staff->bank_branch_code, 3);                  // 4: 受取人支店コード
        $fields .= $this->kanaPad($staff->bank_branch ?? '', 15);                // 5: 受取人支店名
        $fields .= str_repeat(' ', 4);                                           // 6: 手形交換所番号 (空)
        $fields .= $this->numeric($this->accountTypeCode($staff->bank_account_type), 1);  // 7: 預金種目
        $fields .= $this->numeric($staff->bank_account_number ?? '', 7);         // 8: 口座番号
        $fields .= $this->kanaPad($staff->bank_account_holder_kana ?? '', 30);   // 9: 受取人名 (カナ30桁)
        $fields .= $this->numeric($amount, 10);                                  // 10: 振込金額
        $fields .= '0';                                                          // 11: 新規コード 0=その他
        $fields .= $this->numeric($staff->staff_code ?? (string) $sequence, 20); // 12: 顧客コード1 (使わなければ空白)
        $fields .= str_repeat(' ', 20);                                          // 13: 顧客コード2
        $fields .= '0';                                                          // 14: 振込指定区分 0=テレ
        $fields .= str_repeat(' ', 7);                                           // 15: ダミー
        return $this->pad120($fields);
    }

    protected function trailerRecord(int $count, int $amount): string
    {
        $fields  = '8';
        $fields .= $this->numeric($count, 6);    // 合計件数
        $fields .= $this->numeric($amount, 12);  // 合計金額
        $fields .= str_repeat(' ', 101);
        return $this->pad120($fields);
    }

    protected function endRecord(): string
    {
        return $this->pad120('9' . str_repeat(' ', 119));
    }

    // ───── field formatters ─────

    /**
     * Right-justified zero-padded numeric. Strips non-digits.
     */
    protected function numeric(string|int $v, int $width): string
    {
        $s = preg_replace('/\D/', '', (string) $v) ?? '';
        if (strlen($s) > $width) {
            $s = substr($s, -$width);
        }
        return str_pad($s, $width, '0', STR_PAD_LEFT);
    }

    /**
     * Left-justified, half-width-katakana padded with spaces. Width is measured
     * in **SJIS bytes** because that's what the bank reads. Half-kana is 1 byte
     * in SJIS, so post-encode we can simply `str_pad` to width.
     *
     * `mb_convert_kana` flags:
     *   k = full-width katakana → half-width katakana
     *   a = full-width ASCII → half-width ASCII
     *   s = full-width space → half-width space
     */
    protected function kanaPad(string $v, int $width): string
    {
        $half = mb_convert_kana($v, 'kas', 'UTF-8');
        $half = strtoupper($half);
        // Encode to SJIS first, then pad/truncate by byte length — that's what
        // the bank actually validates against.
        $sjis = mb_convert_encoding($half, 'SJIS-win', 'UTF-8');
        if (strlen($sjis) > $width) {
            $sjis = substr($sjis, 0, $width);
        }
        return str_pad($sjis, $width, ' ', STR_PAD_RIGHT);
    }

    protected function pad120(string $s): string
    {
        // $s is already a mix of pure-ASCII (numeric/spaces) and SJIS-encoded
        // kana fields, so byte-length is the right measure.
        if (strlen($s) > 120) {
            return substr($s, 0, 120);
        }
        return str_pad($s, 120, ' ', STR_PAD_RIGHT);
    }

    protected function accountTypeCode(?string $type): string
    {
        // 普通=1 / 当座=2 / 納税準備=3 / 貯蓄=4 / その他=9
        return match (true) {
            $type === '普通' || strcasecmp((string) $type, 'futsuu') === 0 || $type === '1' => '1',
            $type === '当座' || strcasecmp((string) $type, 'touza') === 0  || $type === '2' => '2',
            $type === '貯蓄' || strcasecmp((string) $type, 'chochiku') === 0 || $type === '4' => '4',
            default => '1',
        };
    }

    /**
     * @return array<int,string>  list of missing fields, empty = ready
     */
    protected function missingBankFields(\Fujiba\Accounting\Models\Staff $staff): array
    {
        $missing = [];
        if (! preg_match('/^\d{4}$/', (string) $staff->bank_code))            $missing[] = 'bank_code';
        if (! preg_match('/^\d{3}$/', (string) $staff->bank_branch_code))     $missing[] = 'bank_branch_code';
        if (! preg_match('/^\d+$/', (string) $staff->bank_account_number))    $missing[] = 'bank_account_number';
        if (trim((string) $staff->bank_account_holder_kana) === '')           $missing[] = 'bank_account_holder_kana';
        return $missing;
    }
}
