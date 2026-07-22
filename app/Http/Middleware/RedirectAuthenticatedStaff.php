<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isStaffHost = str_starts_with($request->getHost(), 'staff.');
        $hasStaffSession = Auth::guard('staff')->check();
        $hasAdminSession = Auth::guard('web')->check();

        if ($hasAdminSession || !$isStaffHost) {
            return $next($request);
        }

        if (($request->is('admin*') || $request->path() === '/') && ($isStaffHost || $hasStaffSession)) {
            return redirect('/staff');
        }

        return $next($request);
    }
}
