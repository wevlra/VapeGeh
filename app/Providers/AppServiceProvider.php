<?php

namespace App\Providers;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        DeleteAction::configureUsing(fn (DeleteAction $action) => $action->icon('heroicon-o-trash'));
        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => $action->icon('heroicon-o-trash'));
    }
}
