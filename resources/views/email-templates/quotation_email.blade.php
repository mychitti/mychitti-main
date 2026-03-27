<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
</head>
<body style="margin:0; padding:0; font-family:Arial,Helvetica,sans-serif; background-color:#f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f4; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:{{ $theme_color }}; text-align:center; padding:25px 20px;">
                            @if($store_logo)
                            <img src="{{ $store_logo }}" alt="{{ $store_name }}" style="max-height:50px; margin-bottom:10px; display:block; margin-left:auto; margin-right:auto;">
                            @endif
                            <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:1px;">QUOTATION</h1>
                            <p style="margin:5px 0 0; color:rgba(255,255,255,0.85); font-size:13px;">{{ $email_subject }}</p>
                        </td>
                    </tr>
                    <!-- Body --> 
                    <tr>
                        <td style="padding:30px 30px 10px;">
                            <p style="margin:0 0 15px; color:{{ $theme_color }}; font-weight:700; font-size:15px;">{{ $greeting }}</p>
                            <div style="color:#555555; font-size:14px; line-height:1.7;">
                                {!! nl2br(e($body)) !!}
                            </div>
                        </td>
                    </tr>
                    <!-- Quotation Details -->
                    <tr>
                        <td style="padding:10px 30px 20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f9f9f9; border-radius:6px; border-left:4px solid {{ $theme_color }};">
                                <tr>
                                    <td style="padding:15px 20px;">
                                        <p style="margin:0 0 5px; font-size:13px; color:#777;">Quotation Number</p>
                                        <p style="margin:0; font-size:16px; font-weight:700; color:#333;">{{ $quotation_id }}</p>
                                    </td>
                                    <td style="padding:15px 20px; text-align:right;">
                                        <p style="margin:0 0 5px; font-size:13px; color:#777;">Date</p>
                                        <p style="margin:0; font-size:14px; color:#333;">{{ $quotation_date }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Items Table -->
                    @if(count($items) > 0)
                    <tr>
                        <td style="padding:0 30px 20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="background-color:{{ $theme_color }}; color:#fff; padding:10px 12px; text-align:left; font-size:12px; font-weight:600;">Item</th>
                                        <th style="background-color:{{ $theme_color }}; color:#fff; padding:10px 8px; text-align:center; font-size:12px; font-weight:600;">Qty</th>
                                        <th style="background-color:{{ $theme_color }}; color:#fff; padding:10px 8px; text-align:right; font-size:12px; font-weight:600;">Price</th>
                                        <th style="background-color:{{ $theme_color }}; color:#fff; padding:10px 12px; text-align:right; font-size:12px; font-weight:600;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                    <tr style="background-color:{{ $i % 2 == 0 ? '#ffffff' : '#f9f9f9' }};">
                                        <td style="padding:10px 12px; border-bottom:1px solid #eee; font-size:13px; color:#333;">{{ $item->name }}</td>
                                        <td style="padding:10px 8px; border-bottom:1px solid #eee; font-size:13px; color:#555; text-align:center;">{{ $item->qty }} {{ $item->unit }}</td>
                                        <td style="padding:10px 8px; border-bottom:1px solid #eee; font-size:13px; color:#555; text-align:right;">{{ _price($item->price) }}</td>
                                        <td style="padding:10px 12px; border-bottom:1px solid #eee; font-size:13px; color:#333; font-weight:600; text-align:right;">{{ _price($item->price * $item->qty) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" style="padding:12px; text-align:right; font-weight:700; font-size:14px; color:#333;">Total Amount:</td>
                                        <td style="padding:12px; text-align:right; font-weight:700; font-size:16px; color:{{ $theme_color }};">{{ _price($total_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>
                    @endif
                    <!-- PDF Attachment Note -->
                    <tr>
                        <td style="padding:0 30px 20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef7ff; border-radius:6px;">
                                <tr>
                                    <td style="padding:12px 20px; font-size:13px; color:#336;">
                                        &#128206; <strong>Detailed quotation PDF is attached with this email.</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer Text -->
                    <tr>
                        <td style="padding:0 30px 25px;">
                            <p style="margin:0; color:#888; font-size:13px;">{{ $footer_text }}</p>
                            <br>
                            <p style="margin:0; color:#555; font-size:13px;">Thanks & Regards,</p>
                            <p style="margin:3px 0 0; color:#333; font-size:14px; font-weight:700;">{{ $store_name }}</p>
                        </td>
                    </tr>
                    <!-- Bottom Bar -->
                    <tr>
                        <td style="background-color:{{ $theme_color }}; padding:15px 30px; text-align:center;">
                            <p style="margin:0; color:rgba(255,255,255,0.8); font-size:11px;">&copy; {{ date('Y') }} {{ $store_name }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
