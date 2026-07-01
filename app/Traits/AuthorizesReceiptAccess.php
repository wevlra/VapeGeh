<?php

namespace App\Traits;

use App\Models\StockMovement;

trait AuthorizesReceiptAccess
{
    private function authorizeAccess(StockMovement $stockMovement): void
    {
        $user = auth()->user();

        abort_unless($user, 401);
        abort_unless(in_array($user->role, ['admin', 'staff']), 403);

        if ($user->role === 'staff' && $stockMovement->location_id !== $user->location_id) {
            abort(403);
        }
    }
}
