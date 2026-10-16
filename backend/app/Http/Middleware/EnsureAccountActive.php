<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gesperrte Konten (E-027) kommen bei der nächsten Anfrage nicht mehr weiter, auch wenn noch eine Session besteht.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDeactivated()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // reason, damit die App die Sperre erkennt, ohne Texte zu vergleichen
            return response()->json(['message' => __('accounts.deactivated'), 'reason' => 'account_deactivated'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
