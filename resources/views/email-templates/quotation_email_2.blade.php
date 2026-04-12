<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
</head>
<body style="margin:0; padding:0; font-family:'Segoe UI',Arial,sans-serif; background-color:#f0f2f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f0f2f5; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.10);">
                    <!-- Top accent bar -->
                    <tr>
                        <td style="background:{{ $theme_color }}; height:6px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    <!-- Header -->
                    <tr>
                        <td style="padding:28px 36px 18px; border-bottom:1px solid #f0f0f0;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        @if($store_logo)
                                        <img src="{{ $store_logo }}" alt="{{ $store_name }}" style="max-height:44px; display:block;">
                                        @else
                                        <span style="font-size:18px; font-weight:700; color:{{ $theme_color }};">{{ $store_name }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right; vertical-align:middle;">
                                        <span style="font-size:22px; font-weight:800; color:{{ $theme_color }}; letter-spacing:2px;">QUOTATION</span><br>
                                        <span style="font-size:12px; color:#888;"># {{ $quotation_id }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Meta row -->
                    <tr>
                        <td style="background:#f8f9fc; padding:14px 36px; border-bottom:1px solid #eee;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-size:12px; color:#888;">Date: <strong style="color:#444;">{{ $quotation_date }}</strong></td>
                                    <td style="text-align:right; font-size:12px; color:#888;">Subject: <strong style="color:#444;">{{ $email_subject }}</strong></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Greeting + Body -->
                    <tr>
                        <td style="padding:28px 36px 16px;">
                            <p style="margin:0 0 10px; font-size:15px; font-weight:700; color:{{ $theme_color }};">{{ $greeting }}</p>
                            <div style="font-size:14px; color:#555; line-height:1.75;">{!! nl2br(e($body)) !!}</div>
                        </td>
                    </tr>
                    <!-- Items Table -->
                    @if(count($items) > 0)
                    <tr>
                        <td style="padding:0 36px 20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; border:1px solid #e8e8e8; border-radius:6px; overflow:hidden;">
                                <thead>
                                    <tr>
                                        <th style="background:{{ $theme_color }}; color:#fff; padding:11px 14px; text-align:left; font-size:12px; font-weight:600; width:45%;">Description</th>
                                        <th style="background:{{ $theme_color }}; color:#fff; padding:11px 10px; text-align:center; font-size:12px; font-weight:600;">Qty</th>
                                        <th style="background:{{ $theme_color }}; color:#fff; padding:11px 10px; text-align:right; font-size:12px; font-weight:600;">Unit Price</th>
                                        <th style="background:{{ $theme_color }}; color:#fff; padding:11px 14px; text-align:right; font-size:12px; font-weight:600;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                    <tr>
                                        <td style="padding:10px 14px; border-bottom:1px solid #f0f0f0; font-size:13px; color:#333; background:{{ $i % 2 == 0 ? '#fff' : '#fafafa' }};">{{ $item->name }}</td>
                                        <td style="padding:10px; border-bottom:1px solid #f0f0f0; font-size:13px; text-align:center; color:#555; background:{{ $i % 2 == 0 ? '#fff' : '#fafafa' }};">{{ $item->qty }}{{ $item->unit ? ' '.$item->unit : '' }}</td>
                                        <td style="padding:10px; border-bottom:1px solid #f0f0f0; font-size:13px; text-align:right; color:#555; background:{{ $i % 2 == 0 ? '#fff' : '#fafafa' }};">{{ _price($item->price) }}</td>
                                        <td style="padding:10px 14px; border-bottom:1px solid #f0f0f0; font-size:13px; text-align:right; font-weight:600; color:#333; background:{{ $i % 2 == 0 ? '#fff' : '#fafafa' }};">{{ _price($item->price * $item->qty) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" style="padding:13px 14px; text-align:right; font-size:13px; font-weight:700; color:#444; background:#f8f9fc; border-top:2px solid {{ $theme_color }};">Grand Total</td>
                                        <td style="padding:13px 14px; text-align:right; font-size:16px; font-weight:800; color:{{ $theme_color }}; background:#f8f9fc; border-top:2px solid {{ $theme_color }};">{{ _price($total_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @endif
                    <!-- Attachment note -->
                    <tr>
                        <td style="padding:0 36px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#fff8e1; border-left:4px solid {{ $theme_color }}; border-radius:4px;">
                                <tr><td style="padding:12px 16px; font-size:13px; color:#555;">&#128206; <strong>The detailed quotation PDF is attached.</strong> Please review at your convenience.</td></tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:0 36px 28px;">
                            <p style="margin:0 0 6px; font-size:13px; color:#888;">{{ $footer_text }}</p>
                            <p style="margin:0; font-size:13px; color:#555;">Thanks &amp; Regards,</p>
                            <p style="margin:4px 0 0; font-size:15px; font-weight:700; color:#333;">{{ $store_name }}</p>
                        </td>
                    </tr>
                    <!-- Bottom bar -->
                    <tr>
                        <td style="background:{{ $theme_color }}; padding:14px 36px; text-align:center;">
                            <span style="color:rgba(255,255,255,0.82); font-size:11px;">&copy; {{ date('Y') }} {{ $store_name }}. All rights reserved.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
