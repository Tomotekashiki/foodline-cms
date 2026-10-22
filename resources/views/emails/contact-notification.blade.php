<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ახალი შეტყობინება საიტიდან</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0d0d0e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f4f4f5; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #0d0d0e; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; background-color: #18181b; border: 1px solid #27272a; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
                    <tr>
                        <td style="padding: 28px 36px 20px 36px; background: linear-gradient(180deg, #27272a 0%, #18181b 100%); border-bottom: 1px solid #3f3f46; text-align: center;">
                            <div style="font-size: 22px; font-weight: 800; letter-spacing: 2px; color: #f2ca50; text-transform: uppercase;">
                                Foodline.ge
                            </div>
                            <div style="font-size: 12px; color: #a1a1aa; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-top: 4px;">
                                ახალი კონტაქტის შეტყობინება
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 36px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #121214; border: 1px solid #27272a; border-radius: 10px; overflow: hidden; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; width: 35%; color: #a1a1aa; font-size: 13px;">გამგზავნი:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 600; font-size: 14px;">{{ $msg->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 13px;">ელ-ფოსტა:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; font-size: 14px;">
                                        <a href="mailto:{{ $msg->email }}" style="color: #f2ca50; text-decoration: none; font-weight: 600;">{{ $msg->email }}</a>
                                    </td>
                                </tr>
                                @if(!empty($msg->subject))
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 13px;">თემა:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-size: 14px;">{{ $msg->subject }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 12px 16px; color: #a1a1aa; font-size: 13px; vertical-align: top;">შეტყობინება:</td>
                                    <td style="padding: 12px 16px; color: #ffffff; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $msg->message }}</td>
                                </tr>
                            </table>

                            <div style="text-align: center;">
                                <a href="mailto:{{ $msg->email }}?subject=Re: {{ urlencode($msg->subject ?: 'Foodline.ge') }}" style="display: inline-block; background-color: #f2ca50; color: #000000; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; padding: 12px 28px; border-radius: 8px; text-decoration: none;">
                                    პასუხის გაგზავნა (Reply)
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 36px; background-color: #121214; border-top: 1px solid #27272a; text-align: center; font-size: 12px; color: #71717a;">
                            Foodline.ge
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
