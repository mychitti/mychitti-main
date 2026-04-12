<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
</head>
<body style="margin:0; padding:0; font-family:Georgia,'Times New Roman',serif; background-color:#1a1a2e;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#1a1a2e; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff; border-radius:0; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.4);">
                    <!-- Dark Header -->
                    <tr>
                        <td style="background:#1a1a2e; padding:32px 36px;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        @if($store_logo)
                                        <img src="{{ $store_logo }}" alt="{{ $store_name }}" style="max-height:40px; filter:brightness(0) invert(1);">
                                        @else
                                        <span style="color:#fff; font-size:20px; font-weight:700; font-family:'Segoe UI',Arial,sans-serif;">{{ $store_name }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-block; border:2px solid {{ $theme_color }}; padding:8px 18px; border-radius:4px;">
                                            <span style="color:{{ $theme_color }}; font-size:13px; font-weight:700; font-family:'Segoe UI',Arial,sans-serif; letter-spacing:3px;">QUOTATION</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Accent strip -->
                    <tr>
                        <td style="background:{{ $theme_color }}; height:4px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    <!-- Info bar -->
                    <tr>
                        <td style="background:#f7f7f7; padding:16px 36px; border-bottom:1px solid #eee;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#777; letter-spacing:0.5px;">
                                        REF: <span style="color:#333; font-weight:700;">{{ $quotation_id }}</span>
                                    </td>
                                    <td style="text-align:right; font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#777;">
                                        DATE: <span style="color:#333; font-weight:700;">{{ $quotation_date }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Greeting + Body -->
                    <tr>
                        <td style="padding:32px 36px 20px;">
                            <p style="margin:0 0 14px; font-size:15px; font-weight:700; color:{{ $theme_color }}; font-family:'Segoe UI',Arial,sans-serif;">{{ $greeting }}</p>
                            <div style="font-size:14px; color:#444; line-height:1.85; font-family:'Segoe UI',Arial,sans-serif;">{!! nl2br(e($body)) !!}</div>
                        </td>
                    </tr>
                    <!-- Items -->
                    @if(count($items) > 0)
                    <tr>
                        <td style="padding:0 36px 24px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:2px solid {{ $theme_color }};">
                                        <th style="padding:10px 0; text-align:left; font-size:11px; font-weight:700; color:#777; font-family:'Segoe UI',Arial,sans-serif; letter-spacing:1px; text-transform:uppercase; border-bottom:2px solid {{ $theme_color }};">ITEM</th>
                                        <th style="padding:10px 8px; text-align:center; font-size:11px; font-weight:700; color:#777; font-family:'Segoe UI',Arial,sans-serif; letter-spacing:1px; text-transform:uppercase; border-bottom:2px solid {{ $theme_color }};">QTY</th>
                                        <th style="padding:10px 8px; text-align:right; font-size:11px; font-weight:700; color:#777; font-family:'Segoe UI',Arial,sans-serif; letter-spacing:1px; text-transform:uppercase; border-bottom:2px solid {{ $theme_color }};">RATE</th>
                                        <th style="padding:10px 0; text-align:right; font-size:11px; font-weight:700; color:#777; font-family:'Segoe UI',Arial,sans-serif; letter-spacing:1px; text-transform:uppercase; border-bottom:2px solid {{ $theme_color }};">AMOUNT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                    <tr style="border-bottom:1px solid #f0f0f0;">
                                        <td style="padding:11px 0; font-size:13px; color:#333; font-family:'Segoe UI',Arial,sans-serif;">{{ $item->name }}</td>
                                        <td style="padding:11px 8px; text-align:center; font-size:13px; color:#666; font-family:'Segoe UI',Arial,sans-serif;">{{ $item->qty }}{{ $item->unit ? ' '.$item->unit : '' }}</td>
                                        <td style="padding:11px 8px; text-align:right; font-size:13px; color:#666; font-family:'Segoe UI',Arial,sans-serif;">{{ _price($item->price) }}</td>
                                        <td style="padding:11px 0; text-align:right; font-size:13px; font-weight:600; color:#333; font-family:'Segoe UI',Arial,sans-serif;">{{ _price($item->price * $item->qty) }}</td>
                                    </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" style="padding:14px 0; text-align:right; font-size:13px; font-weight:700; color:#333; font-family:'Segoe UI',Arial,sans-serif; border-top:2px solid {{ $theme_color }};">TOTAL AMOUNT</td>
                                        <td style="padding:14px 0; text-align:right; font-size:18px; font-weight:800; color:{{ $theme_color }}; font-family:'Segoe UI',Arial,sans-serif; border-top:2px solid {{ $theme_color }};">{{ _price($total_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @endif
                    <!-- Attachment note -->
                    <tr>
                        <td style="padding:0 36px 20px;">
                            <div style="background:#f4f4f4; border-radius:4px; padding:12px 16px; font-size:12px; color:#666; font-family:'Segoe UI',Arial,sans-serif;">
                                &#128206; <strong>Quotation PDF attached</strong> — please refer to the attached document for complete details.
                            </div>
                        </td>
                    </tr>
                    <!-- Footer text -->
                    <tr>
                        <td style="padding:0 36px 28px;">
                            <p style="margin:0 0 8px; font-size:13px; color:#888; font-family:'Segoe UI',Arial,sans-serif;">{{ $footer_text }}</p>
                            <p style="margin:0; font-size:13px; color:#555; font-family:'Segoe UI',Arial,sans-serif;">Warm Regards,</p>
                            <p style="margin:4px 0 0; font-size:15px; font-weight:700; color:#1a1a2e; font-family:'Segoe UI',Arial,sans-serif;">{{ $store_name }}</p>
                        </td>
                    </tr>
                    <!-- Dark footer -->
                    <tr>
                        <td style="background:#1a1a2e; padding:16px 36px; text-align:center;">
                            <span style="color:rgba(255,255,255,0.55); font-size:11px; font-family:'Segoe UI',Arial,sans-serif;">&copy; {{ date('Y') }} {{ $store_name }}. All rights reserved.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
