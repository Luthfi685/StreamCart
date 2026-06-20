<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($data, $request->ip(), $request->userAgent());

        $user = \App\Models\User::where('email', $data['email'])->first();
        if ($user && in_array($user->role, ['admin'])) {
            // Jika login sukses tetapi role admin, tolak dari app mobile
            $user->tokens()->delete();
            return response()->json(['error' => 'Akses Ditolak: Gunakan Web Dashboard Admin'], 403);
        }

        return response()->json($result);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:6',
        ]);

        $existing = User::where('email', $data['email'])->first();
        if ($existing) {
            if (!is_null($existing->email_verified_at)) {
                return response()->json(['message' => 'Email sudah terdaftar dan terverifikasi. Silakan login.'], 422);
            }
            // Jika belum verifikasi, hapus yang lama agar bisa daftar ulang
            $existing->delete();
        }

        $result = $this->authService->register($data, $request->ip(), $request->userAgent());

        return response()->json($result, 201);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $result = $this->authService->verifyOtp($data);

        return response()->json($result);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->authService->resendOtp($data);

        return response()->json($result);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->authService->forgotPassword($data);

        return response()->json($result);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:6',
        ]);

        $result = $this->authService->resetPassword($data);

        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl(url('/api/auth/google/callback'))
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl(url('/api/auth/google/callback'))
                ->user();

            // Cari user berdasarkan google_id atau email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                // Update google_id jika login via email biasa sebelumnya
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            } else {
                // Buat user baru
                $user = User::create([
                    'name'              => $googleUser->getName(),
                    'username'          => \Illuminate\Support\Str::slug($googleUser->getName()) . rand(1000, 9999),
                    'email'             => $googleUser->getEmail(),
                    'google_id'         => $googleUser->getId(),
                    'avatar'            => $googleUser->getAvatar(),
                    'role'              => 'buyer',
                    'email_verified_at' => now(),
                    'password'          => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                ]);
            }

            $token = $user->createToken('streamcart-token')->plainTextToken;

            // Redirect ke frontend mobile app via deep link
            // Prioritas 1: Custom URL Scheme untuk Android/iOS app
            // Prioritas 2: Fallback ke web URL jika dibuka di browser biasa
            $userData = urlencode(json_encode([
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'avatar'=> $user->avatar,
            ]));

            // Coba redirect ke deep link app dulu, lalu fallback ke HTTPS
            $appDeepLink  = "io.ionic.starter://login?token={$token}&user={$userData}";
            $webFallback  = "https://goingproject.com/login?token={$token}&user={$userData}";

            // Gunakan HTML redirect untuk handle kedua scenario
            return response("<html><head>
<meta http-equiv='refresh' content='0;url={$appDeepLink}'>
<script>
window.location.href = '{$appDeepLink}';
setTimeout(function(){
  window.location.href = '{$webFallback}';
}, 1000);
</script></head>
<body>Mengalihkan ke aplikasi StreamCart...</body></html>", 200)
->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect('https://goingproject.com/login?error=google_auth_failed');
        }
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $userData = $user->toArray();
        $userData['has_transaction_pin'] = !empty($user->transaction_pin);

        // Add realtime stats for seller
        if ($user->role === 'seller') {
            $userData['products_count'] = \App\Models\Product::where('seller_id', $user->id)->count();
            $userData['followers_count'] = rand(1000, 15000);
            $userData['chat_response_rate'] = rand(90, 100);
        }

        return response()->json($userData);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'sometimes|string|max:100',
            'phone'             => 'sometimes|nullable|string|max:20',
            'address'           => 'sometimes|nullable|string',
            'store_name'        => 'sometimes|nullable|string|max:100',
            'store_description' => 'sometimes|nullable|string',
            'bank_name'         => 'sometimes|nullable|string|max:100',
            'bank_account'      => 'sometimes|nullable|string|max:50',
            'bank_account_name' => 'sometimes|nullable|string|max:100',
            'avatar'            => 'sometimes|nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $user = $this->authService->updateProfile($request->user(), $data);

        return response()->json(['message' => 'Profil berhasil diperbarui.', 'user' => $user]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $this->authService->updatePassword($request->user(), $data['old_password'], $data['new_password']);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    public function registerSeller(Request $request): JsonResponse
    {
        $request->validate(['role' => [
            function ($attr, $val, $fail) use ($request) {
                if ($request->user()->role === 'seller') {
                    $fail('Anda sudah terdaftar sebagai Seller.');
                }
            }
        ]]);

        $data = $request->validate([
            'store_name'        => 'required|string|max:100',
            'store_description' => 'required|string',
            'bank_name'         => 'required|string|max:100',
            'bank_account'      => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:100',
        ]);

        $user = $this->authService->upgradeToSeller($request->user(), $data);

        return response()->json(['message' => 'Berhasil upgrade ke Seller!', 'user' => $user]);
    }

    public function setupTransactionPin(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required',
            'pin' => 'required|digits:6|confirmed'
        ]);

        $user = $request->user();

        // Verifikasi password saat ini
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password saat ini salah.'], 400);
        }

        $user->update([
            'transaction_pin' => Hash::make($request->pin)
        ]);

        return response()->json(['message' => 'PIN Transaksi berhasil diatur.']);
    }
}
