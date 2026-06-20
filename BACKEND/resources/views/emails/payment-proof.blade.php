<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran Baru</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f4f8; font-family: 'Segoe UI', Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8; padding: 40px 0;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                {{-- Header --}}
                <tr>
                    <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px 40px; text-align:center;">
                        <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:-0.5px;">
                            💰 Bukti Pembayaran Masuk!
                        </h1>
                        <p style="margin: 8px 0 0; color: rgba(255,255,255,0.85); font-size:14px;">
                            StreamCart Admin Notification
                        </p>
                    </td>
                </tr>

                {{-- Alert Banner --}}
                <tr>
                    <td style="background:#fff8e1; border-left:4px solid #f59e0b; padding: 16px 40px;">
                        <p style="margin:0; color:#92400e; font-size:14px; font-weight:600;">
                            ⚡ Segera verifikasi bukti pembayaran ini!
                        </p>
                    </td>
                </tr>

                {{-- Order Details --}}
                <tr>
                    <td style="padding: 32px 40px 0;">
                        <h2 style="margin: 0 0 20px; font-size:16px; color:#374151; font-weight:600;">
                            Detail Pesanan
                        </h2>
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="border: 1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                            <tr style="background:#f9fafb;">
                                <td style="padding:12px 16px; font-size:13px; color:#6b7280; width:40%; font-weight:500;">Order ID</td>
                                <td style="padding:12px 16px; font-size:13px; color:#111827; font-weight:700;">#{{ $order->id }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px; font-size:13px; color:#6b7280; border-top:1px solid #e5e7eb; font-weight:500;">Nama Pembeli</td>
                                <td style="padding:12px 16px; font-size:13px; color:#111827; border-top:1px solid #e5e7eb;">{{ $buyer->name }}</td>
                            </tr>
                            <tr style="background:#f9fafb;">
                                <td style="padding:12px 16px; font-size:13px; color:#6b7280; border-top:1px solid #e5e7eb; font-weight:500;">Email Pembeli</td>
                                <td style="padding:12px 16px; font-size:13px; color:#111827; border-top:1px solid #e5e7eb;">{{ $buyer->email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px; font-size:13px; color:#6b7280; border-top:1px solid #e5e7eb; font-weight:500;">Total Pembayaran</td>
                                <td style="padding:12px 16px; font-size:15px; color:#059669; border-top:1px solid #e5e7eb; font-weight:700;">
                                    Rp {{ number_format($order->total_price, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr style="background:#f9fafb;">
                                <td style="padding:12px 16px; font-size:13px; color:#6b7280; border-top:1px solid #e5e7eb; font-weight:500;">Tujuan Transfer</td>
                                <td style="padding:12px 16px; font-size:13px; color:#111827; border-top:1px solid #e5e7eb;">
                                    {{ $order->payment_bank_name }} — {{ $order->payment_bank_account }}<br>
                                    <span style="color:#6b7280;">a.n. {{ $order->payment_bank_account_name }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px; font-size:13px; color:#6b7280; border-top:1px solid #e5e7eb; font-weight:500;">Waktu Upload</td>
                                <td style="padding:12px 16px; font-size:13px; color:#111827; border-top:1px solid #e5e7eb;">
                                    {{ $order->payment_proof_uploaded_at?->format('d M Y, H:i') }} WIB
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- CTA Button --}}
                <tr>
                    <td style="padding: 32px 40px; text-align:center;">
                        <a href="{{ config('app.url') }}/admin/orders/{{ $order->id }}"
                           style="display:inline-block; padding: 14px 36px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                  color:#ffffff; text-decoration:none; border-radius:8px; font-weight:600;
                                  font-size:15px; letter-spacing:0.3px; box-shadow: 0 4px 12px rgba(102,126,234,0.4);">
                            🔍 Verifikasi Sekarang
                        </a>
                        <p style="margin: 16px 0 0; font-size:12px; color:#9ca3af;">
                            Atau masuk ke panel Admin StreamCart dan cari Order #{{ $order->id }}
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f9fafb; border-top:1px solid #e5e7eb; padding: 20px 40px; text-align:center;">
                        <p style="margin:0; font-size:12px; color:#9ca3af;">
                            Email ini dikirim otomatis oleh sistem StreamCart.<br>
                            Jangan balas email ini.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
