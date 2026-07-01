<?php

namespace App\Filament\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class RedirectPanelUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $panelId = Filament::getCurrentPanel()->getId();

        if ($user->role === 'admin' && $panelId !== 'admin') {
            return redirect()->to('/'.(Filament::getPanel('admin')?->getPath() ?? 'admin'));
        }

        if ($user->role === 'staff' && $panelId !== 'staff') {
            return redirect()->to('/'.(Filament::getPanel('staff')?->getPath() ?? 'staff'));
        }

        return $next($request);
    }
}
