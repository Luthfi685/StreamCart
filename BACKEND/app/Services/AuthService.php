<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    public function login(array $credentials, string $ip, string $userAgent): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Blokir login HANYA jika otp_code masih ada (user benar-benar belum verifikasi OTP)
        // Lebih akurat daripada cek email_verified_at karena ada kasus otp_code sudah null
        // tapi email_verified_at belum ter-set (bug lama atau daftar via jalur lain)
        if (!is_null($user->otp_code)) {
            throw ValidationException::withMessages([
                'email' => ['unverified'],
                'message' => ['Akun belum diverifikasi. Silakan masukkan kode OTP yang dikirim ke email Anda.']
            ]);
        }

        // Auto-heal: jika otp_code sudah null tapi email_verified_at belum ter-set,
        // otomatis perbaiki data user agar tidak terjadi masalah di masa depan
        if (is_null($user->email_verified_at)) {
            $user->update(['email_verified_at' => now()]);
        }

        $token = $user->createToken('streamcart-token')->plainTextToken;

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'login',
            'description' => "User {$user->username} berhasil login.",
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
        ]);

        return [
            'token' => $token,
            'user'  => $this->formatUser($user),
        ];
    }

    public function register(array $data, string $ip, string $userAgent): array
    {
        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name'           => $data['name'],
            'username'       => $data['username'] ?? explode('@', $data['email'])[0] . '_' . rand(100, 999),
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'role'           => 'buyer', // Default to buyer
            'phone'          => $data['phone'] ?? null,
            'otp_code'       => $otpCode,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        try {
            // Gunakan queue() bukan send() agar API langsung response tanpa nunggu SMTP
            Mail::to($user->email)->queue(new OtpMail($otpCode));
        } catch (\Exception $e) {
            // Log error or ignore if SMTP is not configured yet
            \Log::error('Gagal kirim email OTP: ' . $e->getMessage());
        }

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'register',
            'description' => "User baru mendaftar (Menunggu Verifikasi OTP): {$user->email}.",
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
        ]);

        return [
            'user_id' => $user->id,
            'email'   => $user->email,
            'message' => 'Silakan cek email Anda untuk kode OTP.'
        ];
    }

    public function verifyOtp(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => 'User tidak ditemukan.']);
        }

        if ($user->otp_code !== $data['otp_code']) {
            throw ValidationException::withMessages(['otp_code' => 'Kode OTP tidak valid.']);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            throw ValidationException::withMessages(['otp_code' => 'Kode OTP sudah kadaluarsa.']);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('streamcart-token')->plainTextToken;

        return [
            'token' => $token,
            'user'  => $this->formatUser($user),
        ];
    }

    public function resendOtp(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => 'User tidak ditemukan.']);
        }

        if (!is_null($user->email_verified_at)) {
            throw ValidationException::withMessages(['email' => 'Akun ini sudah diverifikasi.']);
        }

        // Batasi maksimal 3 kali permintaan per jam
        $key = 'resend-otp:' . $data['email'];
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages(['email' => "Batas pengiriman OTP harian habis. Coba lagi dalam {$minutes} menit."]);
        }

        RateLimiter::hit($key, 3600); // Kunci 1 jam setelah 3 kali mencoba

        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code'       => $otpCode,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        try {
            // Gunakan queue() agar resend OTP tidak membuat user nunggu lama
            Mail::to($user->email)->queue(new OtpMail($otpCode));
        } catch (\Exception $e) {
            \Log::error('Gagal kirim email OTP ulang: ' . $e->getMessage());
        }

        return [
            'message' => 'Kode OTP baru telah dikirim ke email Anda.'
        ];
    }

    public function forgotPassword(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => 'Alamat email tidak terdaftar.']);
        }

        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code'       => $otpCode,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        try {
            // Gunakan queue() agar forgot password tidak lambat karena nunggu SMTP
            Mail::to($user->email)->queue(new ResetPasswordMail($otpCode));
        } catch (\Exception $e) {
            \Log::error('Gagal kirim email Reset Password: ' . $e->getMessage());
        }

        return [
            'message' => 'Kode OTP untuk reset password telah dikirim ke email Anda.'
        ];
    }

    public function resetPassword(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => 'Alamat email tidak valid.']);
        }

        if ($user->otp_code !== $data['otp_code']) {
            throw ValidationException::withMessages(['otp_code' => 'Kode OTP tidak valid.']);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            throw ValidationException::withMessages(['otp_code' => 'Kode OTP sudah kadaluarsa.']);
        }

        $user->update([
            'password'       => Hash::make($data['password']),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        return [
            'message' => 'Password berhasil diubah. Silakan login dengan password baru Anda.'
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'logout',
            'description' => "User {$user->username} logout.",
        ]);
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update(array_filter([
            'name'    => $data['name']  ?? $user->name,
            'phone'   => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
            'avatar'  => $data['avatar'] ?? $user->avatar,
            // Seller fields
            'store_name'        => $data['store_name']        ?? $user->store_name,
            'store_description' => $data['store_description'] ?? $user->store_description,
            'bank_name'         => $data['bank_name']         ?? $user->bank_name,
            'bank_account'      => $data['bank_account']      ?? $user->bank_account,
            'bank_account_name' => $data['bank_account_name'] ?? $user->bank_account_name,
        ], fn($v) => $v !== null));

        return $user->fresh();
    }

    public function updatePassword(User $user, string $oldPassword, string $newPassword): void
    {
        if (! Hash::check($oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }

    public function upgradeToSeller(User $user, array $data): User
    {
        $user->update([
            'role'              => 'seller',
            'store_name'        => $data['store_name'],
            'store_description' => $data['store_description'],
            'bank_name'         => $data['bank_name'],
            'bank_account'      => $data['bank_account'],
            'bank_account_name' => $data['bank_account_name'],
        ]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'upgrade_to_seller',
            'description' => "{$user->username} telah upgrade ke Seller.",
        ]);

        return $user->fresh();
    }

    private function formatUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'username'    => $user->username,
            'email'       => $user->email,
            'role'        => $user->role,
            'phone'       => $user->phone,
            'avatar'      => $user->avatar,
            'store_name'  => $user->store_name,
            'store_description' => $user->store_description,
            'bank_name'   => $user->bank_name,
            'bank_account'=> $user->bank_account,
        ];
    }
}
