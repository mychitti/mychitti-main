<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Link unavailable</title>
    <style>
        body {
            margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
            background:#f6f8fb; color:#1e293b; padding:24px;
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
        }
        .box { background:#fff; border:1px solid #e6e9ef; border-radius:14px; padding:32px 26px;
            max-width:420px; text-align:center; }
        h1 { font-size:18px; margin:0 0 10px; }
        p { font-size:14px; line-height:1.6; color:#64748b; margin:0; }
    </style>
</head>
<body>
    <div class="box">
        <h1>This link isn’t available</h1>
        <p>{{ $reason ?? 'This link is no longer valid.' }}</p>
        <p style="margin-top:10px;">Please contact the hospital and ask them to share it again.</p>
    </div>
</body>
</html>
