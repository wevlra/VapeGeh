<?php

namespace App\Filament\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class RedirectPanelUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        $panelId = Filament::getCurrentPanel()->getId();
        $user = $panelId === 'staff' ? auth('staff')->user() : auth('web')->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->role === 'admin' && $panelId !== 'admin') {
            return redirect()->to('/'.(Filament::getPanel('admin')?->getPath() ?? 'admin'));
        }

        if ($user->role === 'staff' && $panelId !== 'staff') {
            return redirect()->to('/'.(Filament::getPanel('staff')?->getPath() ?? 'staff'));
        }

        return $next($request);
    }
}
