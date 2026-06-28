<?php

namespace App\Providers\Filament;

use App\Filament\Staff\Widgets\StaffPaymentMethodChart;
use App\Filament\Staff\Widgets\StaffSalesChart;
use App\Filament\Staff\Widgets\StaffStatsOverview;
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

class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('staff')
            ->path('staff')
            ->viteTheme('resources/css/filament/staff/theme.css')
            ->login()
            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(asset('assets/images/logo-wordmark-light-tr.png'))
            ->darkModeBrandLogo(asset('assets/images/logo-wordmark-dark-tr.png'))
            ->brandLogoHeight('4rem')
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
                            ->url('/staff')
                            ->isActive(fn () => request()->is('staff')),
                        MobileBottomNavItem::make('Products')
                            ->icon('heroicon-o-archive-box')
                            ->activeIcon('heroicon-s-archive-box')
                            ->url('/staff/products')
                            ->isActive(fn () => request()->is('staff/products*')),
                        MobileBottomNavItem::make('Sales')
                            ->icon('heroicon-o-banknotes')
                            ->activeIcon('heroicon-s-banknotes')
                            ->url('/staff/sales')
                            ->isActive(fn () => request()->is('staff/sales*')),
                    ]),
            ])
            ->discoverResources(in: app_path('Filament/Staff/Resources'), for: 'App\Filament\Staff\Resources')
            ->discoverPages(in: app_path('Filament/Staff/Pages'), for: 'App\Filament\Staff\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                StaffStatsOverview::class,
                StaffSalesChart::class,
                StaffPaymentMethodChart::class,
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
