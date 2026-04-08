<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực email — E-Learning</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f0f4f8; padding: 40px 20px;">
        <tr>
            <td align="center">
                {{-- Card chính --}}
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);">

                    {{-- Header gradient --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 40px 30px; text-align: center;">
                            {{-- Logo / Icon --}}
                            <div style="width: 64px; height: 64px; background-color: rgba(255, 255, 255, 0.2); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                <span style="font-size: 32px; color: #ffffff;">📧</span>
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">
                                Xác thực email của bạn
                            </h1>
                            <p style="margin: 8px 0 0; color: rgba(255, 255, 255, 0.85); font-size: 14px;">
                                Chỉ còn một bước nữa để hoàn tất đăng ký
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 36px 40px;">
                            <p style="margin: 0 0 20px; color: #334155; font-size: 16px; line-height: 1.6;">
                                Xin chào <strong style="color: #1e293b;">{{ $studentName }}</strong>,
                            </p>

                            <p style="margin: 0 0 24px; color: #64748b; font-size: 15px; line-height: 1.7;">
                                Cảm ơn bạn đã đăng ký tài khoản tại <strong style="color: #667eea;">E-Learning</strong>.
                                Vui lòng nhấn vào nút bên dưới để xác thực địa chỉ email và kích hoạt tài khoản của bạn.
                            </p>

                            {{-- CTA Button --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 32px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $verifyUrl }}"
                                           target="_blank"
                                           style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; padding: 14px 48px; border-radius: 50px; letter-spacing: 0.3px; box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);">
                                            ✉️ Xác thực email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Divider --}}
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 28px 0;">

                            {{-- Fallback link --}}
                            <p style="margin: 0 0 8px; color: #94a3b8; font-size: 13px;">
                                Nếu nút không hoạt động, bạn có thể sao chép và dán link sau vào trình duyệt:
                            </p>
                            <p style="margin: 0; word-break: break-all;">
                                <a href="{{ $verifyUrl }}" style="color: #667eea; font-size: 13px; text-decoration: underline;">
                                    {{ $verifyUrl }}
                                </a>
                            </p>

                            {{-- Expiry notice --}}
                            <div style="margin-top: 24px; background-color: #fefce8; border-left: 4px solid #eab308; border-radius: 8px; padding: 14px 16px;">
                                <p style="margin: 0; color: #854d0e; font-size: 13px; line-height: 1.5;">
                                    ⏳ <strong>Lưu ý:</strong> Link xác thực có hiệu lực trong <strong>24 giờ</strong>.
                                    Sau thời gian này, bạn cần đăng ký lại để nhận link mới.
                                </p>
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0 0 4px; color: #94a3b8; font-size: 12px;">
                                Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.
                            </p>
                            <p style="margin: 0; color: #cbd5e1; font-size: 12px;">
                                © {{ date('Y') }} E-Learning. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
