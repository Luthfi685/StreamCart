<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Akses Ditolak: Role tidak diizinkan.'], 403);
            }
            abort(403, 'Akses Ditolak: Role tidak diizinkan.');
        }

        return $next($request);
    }
}
