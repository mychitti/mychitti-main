<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $company_name }}</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f4; font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 15px; color: #444; }
        .wrapper { width: 100%; background: #f4f4f4; padding: 40px 0; }
        .card { width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #006161; padding: 32px 40px; text-align: center; }
        .header img { height: 48px; object-fit: contain; }
        .body { padding: 40px 40px 32px; }
        .body h2 { margin: 0 0 16px; font-size: 22px; color: #006161; }
        .body p { margin: 0 0 16px; line-height: 1.7; }
        .btn-wrap { text-align: center; margin: 32px 0 24px; }
        .btn { display: inline-block; background: #006161; color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; }
        .note { background: #f0f8f8; border-left: 4px solid #006161; padding: 14px 18px; border-radius: 4px; font-size: 13px; color: #555; margin-bottom: 24px; }
        .footer { background: #f9f9f9; border-top: 1px solid #eee; padding: 24px 40px; text-align: center; font-size: 12px; color: #999; }
        .footer a { color: #006161; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                @php $logo = \App\Models\BusinessSetting::where('key', 'logo')->value('value'); @endphp
                @if($logo)
                    <img src="{{ asset('storage/business/' . $logo) }}" alt="{{ $company_name }}">
                @else
                    <span style="color:#fff; font-size:20px; font-weight:700;">{{ $company_name }}</span>
                @endif
            </div>
            <div class="body">
                <h2>Welcome to {{ $company_name }}!</h2>
                <p>Hi <strong>{{ $storeName }}</strong>,</p>
                <p>Your store has been successfully registered on <strong>{{ $company_name }}</strong>. You can now log in to your vendor panel and start managing your store.</p>

                <div class="note">
                    You can log in using your registered mobile number via OTP — no password needed.
                </div>

                <div class="btn-wrap">
                    <a href="{{ $login_url }}" class="btn">Login to Your Store</a>
                </div>

                <p style="font-size:13px; color:#777;">Or copy and paste this link into your browser:<br>
                    <a href="{{ $login_url }}" style="color:#006161;">{{ $login_url }}</a>
                </p>

                <p>If you need any help getting started, feel free to contact our support team.</p>

                <p>Thanks &amp; Regards,<br><strong>{{ $company_name }}</strong></p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ $company_name }}. All rights reserved.<br>
                <a href="https://vendor.mcvendorhub.com">vendor.mcvendorhub.com</a>
            </div>
        </div>
    </div>
</body>
</html>
