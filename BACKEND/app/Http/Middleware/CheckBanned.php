<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBanned
{
    /**
     * Cek apakah user yang sedang login statusnya di-ban.
     * - Web request  → logout + redirect ke /login dengan pesan error
     * - API request  → JSON 401
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Akun Anda Telah Ditangguhkan oleh Admin.',
                    'reason'  => $user->ban_reason,
                ], 401);
            }

            // Web: logout lalu redirect ke login dengan flash error
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda Telah Ditangguhkan oleh Admin. Hubungi support jika ini adalah kesalahan.']);
        }

        return $next($request);
    }
}
