<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class SellerRegistrationController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        $tokenStr = $request->query('token');
        if (!$tokenStr) {
            abort(403, 'Akses Ditolak: Token tidak ditemukan.');
        }

        $token = PersonalAccessToken::findToken($tokenStr);
        if (!$token || !$token->tokenable) {
            abort(403, 'Akses Ditolak: Token tidak valid atau kedaluwarsa.');
        }

        $user = $token->tokenable;
        Auth::login($user); // Login otomatis ke session web
        
        if ($user->role === 'seller' || $user->role === 'admin') {
            return redirect('/seller/dashboard'); // Sudah seller
        }

        return view('seller-register', ['token' => $tokenStr, 'user' => $user]);
    }

    public function upgradeToSeller(Request $request)
    {
        $tokenStr = $request->input('token');
        $token = PersonalAccessToken::findToken($tokenStr);
        if (!$token || !$token->tokenable) {
            abort(403, 'Akses Ditolak: Token tidak valid.');
        }

        $user = $token->tokenable;

        if ($user->role === 'buyer') {
            $data = $request->validate([
                'store_name' => 'required|string|max:100',
                'store_description' => 'required|string',
                'bank_name' => 'required|string|max:100',
                'bank_account' => 'required|string|max:50',
                'bank_account_name' => 'required|string|max:100',
            ]);

            $user->update([
                'role' => 'seller',
                'store_name' => $data['store_name'],
                'store_description' => $data['store_description'],
                'bank_name' => $data['bank_name'],
                'bank_account' => $data['bank_account'],
                'bank_account_name' => $data['bank_account_name'],
            ]);
        }

        // Login session web untuk user ini
        Auth::login($user);

        return redirect('/seller/dashboard');
    }
}
