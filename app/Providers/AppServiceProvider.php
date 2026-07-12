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

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
            fn (): string => \Illuminate\Support\Facades\Blade::render('
                <div class="px-2" x-data="{
                    savedPrinter: null,
                    init() {
                        this.savedPrinter = JSON.parse(localStorage.getItem(\'savedPrinter\'));
                        window.addEventListener(\'printer-connected\', () => {
                            this.savedPrinter = JSON.parse(localStorage.getItem(\'savedPrinter\'));
                        });
                    }
                }">
                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-printer"
                        x-on:click.prevent="$dispatch(\'open-printer-modal\')"
                        size="sm"
                    >
                        <span x-text="savedPrinter ? (savedPrinter.name || \'Printer Terhubung\') : \'Pilih Printer\'"></span>
                    </x-filament::button>
                </div>
            '),
        );
    }
}
