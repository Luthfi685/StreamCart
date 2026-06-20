<!DOCTYPE html>
<html>
<head>
    <title>Kode OTP Reset Password StreamCart</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #333333; text-align: center;">Reset Password Anda</h2>
        <p style="color: #555555; font-size: 16px; line-height: 1.5;">
            Kami menerima permintaan untuk melakukan reset password pada akun StreamCart Anda.
            Silakan masukkan kode OTP berikut untuk melanjutkan proses reset password:
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; padding: 15px 30px; background-color: #f0f8ff; color: #0058be; font-size: 24px; font-weight: bold; letter-spacing: 5px; border-radius: 8px; border: 2px dashed #0058be;">
                {{ $otpCode }}
            </span>
        </div>

        <p style="color: #555555; font-size: 16px; line-height: 1.5;">
            Kode OTP ini hanya berlaku selama <strong>5 menit</strong>. Jangan berikan kode ini kepada siapa pun.
        </p>
        
        <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;">
        <p style="color: #999999; font-size: 12px; text-align: center;">
            Jika Anda tidak meminta reset password, abaikan email ini.
        </p>
    </div>
</body>
</html>
