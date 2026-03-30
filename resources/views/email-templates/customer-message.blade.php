<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $subject }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        @media screen {
            @font-face {
                font-family: 'Source Sans Pro';
                font-style: normal;
                font-weight: 400;
                src: local('Source Sans Pro Regular'), local('SourceSansPro-Regular'), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format('woff');
            }
            @font-face {
                font-family: 'Source Sans Pro';
                font-style: normal;
                font-weight: 700; 
                src: local('Source Sans Pro Bold'), local('SourceSansPro-Bold'), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format('woff');
            }
        }

        body, table, td, a {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }
        table, td {
            mso-table-rspace: 0pt;
            mso-table-lspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
        }
        a[x-apple-data-detectors] {
            font-family: inherit !important;
            font-size: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
            color: inherit !important;
            text-decoration: none !important;
        }
        div[style*="margin: 16px 0;"] {
            margin: 0 !important;
        }
        body {
            width: 100% !important;
            height: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif;
        }
        table {
            border-collapse: collapse !important;
        }
        a {
            color: #1a82e2;
        }
        img {
            height: auto;
            line-height: 100%;
            text-decoration: none;
            border: 0;
            outline: none;
        }
    </style>
</head>
<body style="background-color: #f4f5f7;">

    <!-- Header -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" bgcolor="#f4f5f7" style="padding: 30px 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px;">

                    <!-- Logo -->
                    <tr>
                        <td align="center" bgcolor="#ffffff" style="padding: 30px 40px 20px; border-radius: 8px 8px 0 0;">
                            @php
                                $logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value;
                                $businessName = \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value;
                            @endphp
                            <img src="{{ asset('storage/app/public/business/' . $logo) }}" alt="{{ $businessName }}" width="150" style="display: block; max-width: 150px;">
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 40px 0;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #333333;">
                                Hello {{ $name }},
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 40px 0;">
                            <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #555555;">
                                Thank you for reaching out to us. Please find our response to your enquiry below.
                            </p>
                        </td>
                    </tr> 

                    <!-- Reply Message -->
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 20px 40px;">
                            <p style="margin: 0 0 5px; font-size: 13px; color: #999999; text-transform: uppercase; letter-spacing: 0.5px;">Our Reply</p>
                            <div style="padding: 15px 20px; background-color: #f0f7ff; border-left: 4px solid #1a82e2; border-radius: 4px; font-size: 15px; line-height: 1.6; color: #333333;">
                                {!! nl2br(e($reply_message)) !!} 
                            </div>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #eeeeee; margin: 0;">
                        </td>
                    </tr>

                    <!-- Original Message -->
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 20px 40px;">
                            <p style="margin: 0 0 5px; font-size: 13px; color: #999999; text-transform: uppercase; letter-spacing: 0.5px;">Your Message</p>
                            @if(!empty($customer_subject))
                                <p style="margin: 5px 0 10px; font-size: 14px; font-weight: 600; color: #666666;">
                                    {{ $customer_subject }}
                                </p>
                            @endif
                            <div style="padding: 15px 20px; background-color: #f9f9f9; border-left: 4px solid #cccccc; border-radius: 4px; font-size: 14px; line-height: 1.6; color: #666666;">
                                {!! nl2br(e($customer_message)) !!}
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 20px 40px 30px; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #666666;">
                                Thank you,<br>
                                <strong>{{ $businessName }}</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Bottom Note -->
                    <tr>
                        <td align="center" style="padding: 20px 40px;">
                            <p style="margin: 0; font-size: 12px; color: #aaaaaa;">
                                This is a reply to your enquiry. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
