<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Reset Your Password</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; box-sizing: border-box; background-color: #f3f4f6; color: #374151; height: 100%; hyphens: auto; line-height: 1.6; margin: 0; -moz-hyphens: auto; -ms-word-break: break-all; width: 100% !important; -webkit-hyphens: auto; -webkit-text-size-adjust: none; word-break: break-word;">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }
            .footer {
                width: 100% !important;
            }
        }
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>

    <div style="background-color: #f3f4f6; margin: 0; padding: 20px 0; width: 100%; text-align: center;">
        <table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto; padding: 0; width: 100%; text-align: center;">
            <tr>
                <td align="center" style="padding: 20px 0; text-align: center;">
                    <a href="{{ config('app.url') }}" style="color: #3d4852; font-size: 24px; font-weight: bold; text-decoration: none; text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4);">
                        {{ config('app.name') }}
                    </a>
                </td>
            </tr>

            <!-- Email Body -->
            <tr>
                <td style="width: 100%; margin: 0; padding: 0;" align="center">
                    <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); margin: 0 auto; padding: 0; width: 570px;">
                        
                        <!-- Body content -->
                        <tr>
                            <td style="padding: 35px;">
                                <h1 style="color: #111827; font-size: 22px; font-weight: bold; margin-top: 0; text-align: left;">
                                    Hello!
                                </h1>
                                
                                <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #4b5563;">
                                    You are receiving this email because we received a password reset request for your account on <strong>{{ config('app.name') }}</strong>.
                                </p>

                                <table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 30px auto; padding: 0; text-align: center; width: 100%;">
                                    <tr>
                                        <td align="center">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                    <td align="center">
                                                        <a href="{{ $url }}" class="button" target="_blank" rel="noopener" style="position: relative; -webkit-text-size-adjust: none; border-radius: 6px; color: #fff; display: inline-block; text-decoration: none; background-color: #2563eb; border-top: 12px solid #2563eb; border-right: 24px solid #2563eb; border-bottom: 12px solid #2563eb; border-left: 24px solid #2563eb; font-weight: 500; font-size: 16px;">
                                                            Reset Password
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #4b5563;">
                                    This password reset link will expire in {{ $count }} minutes.
                                </p>

                                <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #4b5563;">
                                    If you did not request a password reset, no further action is required. Your account remains secure.
                                </p>

                                <hr style="border: none; border-bottom: 1px solid #e5e7eb; margin: 25px 0;">

                                <!-- Subcopy -->
                                <p style="font-size: 14px; line-height: 1.5em; margin-top: 0; text-align: left; color: #6b7280;">
                                    If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
                                    <br><br>
                                    <a href="{{ $url }}" style="color: #2563eb; text-decoration: underline; word-break: break-all;">
                                        {{ $url }}
                                    </a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td style="text-align: center; margin: 0; padding: 25px 0;">
                    <p style="color: #6b7280; font-size: 12px; line-height: 1.5em; margin-top: 0; text-align: center;">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                        Delivered reliably with Brevo.
                    </p>
                </td>
            </tr>

        </table>
    </div>
</body>
</html>
