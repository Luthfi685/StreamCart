<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode OTP StreamCart</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <h2 style="color: #333333; text-align: center;">Selamat Datang di StreamCart!</h2>
        <p style="color: #555555; font-size: 16px;">Halo,</p>
        <p style="color: #555555; font-size: 16px;">Terima kasih telah mendaftar di StreamCart. Untuk menyelesaikan proses pendaftaran Anda, silakan gunakan kode OTP berikut:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <span style="font-size: 32px; font-weight: bold; color: #0058be; background-color: #f0f4f8; padding: 10px 20px; border-radius: 8px; letter-spacing: 4px;">{{ $otpCode }}</span>
        </div>
        
        <p style="color: #555555; font-size: 14px; text-align: center;">Kode OTP ini hanya berlaku selama <strong>5 menit</strong>.</p>
        <p style="color: #777777; font-size: 12px; text-align: center; margin-top: 30px;">Jika Anda tidak merasa mendaftar akun ini, silakan abaikan email ini.</p>
    </div>
</body>
</html>
