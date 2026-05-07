<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $context['subject'] ?? '' }}</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7f9;padding:24px;color:#1f2937">
    <div style="max-width:600px;margin:0 auto;background:#fff;padding:24px;border-radius:8px">
        {!! nl2br(e($body)) !!}
    </div>
</body>
</html>
