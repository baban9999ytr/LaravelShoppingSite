<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('categories*')) {
            $isInertia = $request->header('X-Inertia') ? 'YES' : 'NO';
            $user = $request->user() ? $request->user()->id : 'GUEST';
            $userAgent = substr($request->header('User-Agent', 'Unknown'), 0, 40);

            error_log("------------------------------------------------");
            error_log(sprintf(
                "[DEBUG LOG] %s %s | Inertia: %s | User: %s | UA: %s",
                $request->method(),
                $request->fullUrl(),
                $isInertia,
                $user,
                $userAgent
            ));
            error_log("------------------------------------------------");
        }

        return $next($request);
    }
}