<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TauriDetectionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('__tauri') || $request->session()->get('__tauri')) {
            $request->session()->put('__tauri', true);
            $request->attributes->set('__tauri', true);
        }

        return $next($request);
    }
}
