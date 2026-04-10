<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }}</title>
<style>
  body { margin:0; padding:0; background:#f4f4f4; font-family:Arial,sans-serif; }
  .wrap { max-width:600px; margin:30px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .header { background:#1abc9c; padding:28px 32px; color:#fff; }
  .header h1 { margin:0; font-size:22px; }
  .header p { margin:4px 0 0; opacity:.85; font-size:13px; }
  .body { padding:28px 32px; }
  .body img { max-width:100%; border-radius:6px; margin-bottom:16px; }
  .body p { color:#444; line-height:1.7; font-size:15px; margin:0; }
  .footer { background:#f9f9f9; border-top:1px solid #eee; padding:16px 32px; text-align:center; color:#aaa; font-size:12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>{{ $title }}</h1>
    <p>{{ $company }}</p>
  </div>
  <div class="body">
    @if($image)
      <img src="{{ $image }}" alt="">
    @endif
    <p>{{ $message }}</p>
  </div>
  <div class="footer">&copy; {{ date('Y') }} {{ $company }}. All rights reserved.</div>
</div>
</body>
</html>
