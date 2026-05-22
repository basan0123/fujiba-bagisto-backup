<!DOCTYPE html>
<html lang="ja">
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; color:#1F2937; font-size:14px; line-height:1.5;">
    <p>{{ $invoice['recipientName'] ?: 'お客様' }} 様</p>

    <p>このたびはご利用ありがとうございました。<br>
       適格請求書（No. {{ $invoice['incrementId'] ?? $invoice['id'] }}）を添付ファイルにてお送りいたします。</p>

    <table cellpadding="6" cellspacing="0" border="0" style="margin: 12px 0; font-size: 14px;">
        <tr><td style="color:#6B7280;">合計</td>
            <td><strong>¥{{ number_format($invoice['grandTotal']) }}</strong></td></tr>
        <tr><td style="color:#6B7280;">うち消費税</td>
            <td>¥{{ number_format($invoice['taxAmount']) }}</td></tr>
        @if ($invoice['issuerRegistrationNumber'])
            <tr><td style="color:#6B7280;">登録番号</td>
                <td>{{ $invoice['issuerRegistrationNumber'] }}</td></tr>
        @endif
    </table>

    <p style="color:#6B7280; font-size:12px;">
        本メールは自動送信です。お心当たりがない場合は破棄してください。
    </p>
</body>
</html>
