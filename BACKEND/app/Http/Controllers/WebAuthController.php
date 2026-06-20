<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        $stats = [
            'sellers'  => \App\Models\User::where('role', 'seller')->count(),
            'products' => \App\Models\Product::count(),
            'buyers'   => \App\Models\User::where('role', 'buyer')->count(),
            'lives'    => \App\Models\LiveSession::count(),
        ];

        return view('auth.login', compact('stats'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role === 'buyer') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Akses Ditolak: Gunakan aplikasi mobile StreamCart untuk Buyer.',
                ])->onlyInput('email');
            }

            return $this->redirectByRole($user);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            } else {
                // Auto-create as a new seller/user (can be changed later by admin)
                $user = User::create([
                    'name'              => $googleUser->getName(),
                    'username'          => \Illuminate\Support\Str::slug($googleUser->getName()) . rand(1000, 9999),
                    'email'             => $googleUser->getEmail(),
                    'google_id'         => $googleUser->getId(),
                    'avatar'            => $googleUser->getAvatar(),
                    'role'              => 'seller', // Web login defaults to seller
                    'email_verified_at' => now(),
                    'password'          => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                ]);
            }

            if ($user->role === 'buyer') {
                return redirect('/login')->withErrors([
                    'email' => 'Akses Ditolak: Akun Google Anda terdaftar sebagai Buyer. Gunakan aplikasi mobile StreamCart.',
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();

            return $this->redirectByRole($user);

        } catch (\Exception $e) {
            Log::error('Google Web OAuth Error: ' . $e->getMessage());
            return redirect('/login')->withErrors([
                'email' => 'Login Google gagal: ' . $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function redirectByRole($user)
    {
        return match($user->role) {
            'admin'  => redirect('/admin/dashboard'),
            'seller' => redirect('/seller/dashboard'),
            default  => redirect('/login'),
        };
    }
}
