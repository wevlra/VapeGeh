<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\AdminExpenseCategoryChart;
use App\Filament\Admin\Widgets\AdminPaymentMethodChart;
use App\Filament\Admin\Widgets\AdminSalesChart;
use App\Filament\Admin\Widgets\AdminStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Hammadzafar05\MobileBottomNav\MobileBottomNav;
use Hammadzafar05\MobileBottomNav\MobileBottomNavItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wezlo\FilamentResponsiveTable\FilamentResponsiveTablePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->plugins([
                FilamentResponsiveTablePlugin::make()
                    ->defaultBreakpoint('md'),
                MobileBottomNav::make()
                    ->items([
                        MobileBottomNavItem::make('Dashboard')
                            ->icon('heroicon-o-home')
                            ->activeIcon('heroicon-s-home')
                            ->url('/admin')
                            ->isActive(fn () => request()->is('admin')),
                        MobileBottomNavItem::make('Products')
                            ->icon('heroicon-o-archive-box')
                            ->activeIcon('heroicon-s-archive-box')
                            ->url('/admin/products')
                            ->isActive(fn () => request()->is('admin/products*')),
                        MobileBottomNavItem::make('Sales')
                            ->icon('heroicon-o-banknotes')
                            ->activeIcon('heroicon-s-banknotes')
                            ->url('/admin/sales')
                            ->isActive(fn () => request()->is('admin/sales*')),
                    ]),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AdminStatsOverview::class,
                AdminSalesChart::class,
                AdminPaymentMethodChart::class,
                AdminExpenseCategoryChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
