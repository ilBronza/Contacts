<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('contacts::verification.subject') }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { padding: 16px 8px !important; }
            .email-content { padding: 28px 24px !important; }
            .email-heading { font-size: 26px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f1f4f7; color:#233746; font-family:Arial, Helvetica, sans-serif; -webkit-text-size-adjust:100%;">
<div style="display:none; font-size:1px; line-height:1px; color:#f1f4f7; max-height:0; max-width:0; opacity:0; overflow:hidden; mso-hide:all;">{{ __('contacts::verification.preheader') }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f4f7">
    <tr>
        <td align="center" class="email-shell" style="padding:40px 16px;">
            <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%; max-width:600px; border:1px solid #dfe6ec; border-radius:8px;">
                <tr>
                    <td align="center" style="padding:32px 24px; border-bottom:1px solid #e9eef2;">
                        @if ($logoPath)
                            <img src="{{ $message->embed($logoPath) }}" width="220" alt="{{ $brandName }}" style="display:block; width:220px; max-width:100%; height:auto; border:0; color:#005580; font-size:24px; font-weight:bold;">
                        @else
                            <p style="margin:0; color:#005580; font-size:24px; line-height:32px; font-weight:bold;">{{ $brandName }}</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="email-content" style="padding:36px 40px 32px;">
                        <h1 class="email-heading" style="margin:0 0 20px; color:#17344a; font-size:30px; line-height:1.25; font-weight:bold;">{{ __('contacts::verification.subject') }}</h1>
                        <p style="margin:0 0 28px; font-size:16px; line-height:1.65;">{{ __('contacts::verification.introduction') }}</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
                            <tr>
                                <td align="center" bgcolor="#005580" style="border-radius:5px; mso-padding-alt:16px 24px;">
                                    <a href="{{ $verificationUrl }}" style="display:inline-block; padding:16px 24px; border:1px solid #005580; border-radius:5px; color:#ffffff; font-size:16px; line-height:20px; font-weight:bold; text-decoration:none; mso-padding-alt:0;">{{ __('contacts::verification.action') }}</a>
                                </td>
                            </tr>
                        </table>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f0f6fa" style="border-left:3px solid #005580;">
                            <tr>
                                <td style="padding:16px 18px; color:#35556b; font-size:14px; line-height:1.6;">{{ __('contacts::verification.expiration', ['days' => $expiresInDays]) }}</td>
                            </tr>
                        </table>
                        <p style="margin:24px 0 0; color:#647583; font-size:14px; line-height:1.6;">{{ __('contacts::verification.ignore') }}</p>
                    </td>
                </tr>
                <tr>
                    <td class="email-content" style="padding:24px 40px 28px; border-top:1px solid #e9eef2;">
                        <p style="margin:0 0 8px; color:#647583; font-size:12px; line-height:1.6;">{{ __('contacts::verification.fallback') }}</p>
                        <p style="margin:0; font-size:12px; line-height:1.65; word-break:break-all; overflow-wrap:anywhere;"><a href="{{ $verificationUrl }}" style="color:#005580; text-decoration:underline; word-break:break-all; overflow-wrap:anywhere;">{{ $verificationUrl }}</a></p>
                    </td>
                </tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
            <p style="margin:20px 0 0; color:#647583; font-size:12px; line-height:1.6;">{{ $brandName }}</p>
        </td>
    </tr>
</table>
</body>
</html>
