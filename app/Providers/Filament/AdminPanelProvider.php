<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\Pos;
use App\Filament\Admin\Pages\StockIn;
use App\Filament\Admin\Pages\StockOut;
use App\Filament\Admin\Widgets\AdminExpenseCategoryChart;
use App\Filament\Admin\Widgets\AdminPaymentMethodChart;
use App\Filament\Admin\Widgets\AdminSalesChart;
use App\Filament\Admin\Widgets\AdminStatsOverview;
use App\Filament\Admin\Widgets\AdminStatsOverviewMobile;
use App\Filament\Admin\Widgets\OperationalQuickActionsWidget;
use App\Filament\Admin\Widgets\StockQuickActionsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->profile()
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
                        MobileBottomNavItem::make('History')
                            ->icon('heroicon-o-clock')
                            ->activeIcon('heroicon-s-clock')
                            ->url('/admin/history/stock-movements')
                            ->isActive(fn () => request()->is('admin/history*')),
                    ]),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
                Pos::class,
                StockIn::class,
                StockOut::class,
            ])
            ->widgets([
                AdminStatsOverview::class,
                AdminStatsOverviewMobile::class,
                StockQuickActionsWidget::class,
                OperationalQuickActionsWidget::class,
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
