<?php

namespace App\Providers\Filament;

use App\Http\Controllers\FinishFirstVisitController;
use App\Http\Middleware\EnsureSetupWizardComplete;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('company')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName('ClientLoop')
            ->brandLogo(new HtmlString('<span style="display:flex;align-items:center;gap:.5rem;font-size:1.2rem;font-weight:800;letter-spacing:-.04em;color:#172033"><img src="/images/clientloop-symbol.png" alt="" style="width:2rem;height:2rem"><span>Client<span style="color:#0f766e">Loop</span></span></span>'))
            ->darkModeBrandLogo(new HtmlString('<span style="display:flex;align-items:center;gap:.5rem;font-size:1.2rem;font-weight:800;letter-spacing:-.04em;color:#f8fafc"><img src="/images/clientloop-symbol.png" alt="" style="width:2rem;height:2rem"><span>Client<span style="color:#7dd3c7">Loop</span></span></span>'))
            ->brandLogoHeight('2rem')
            ->favicon('/images/clientloop-symbol.png')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->databaseNotifications()
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
                EnsureSetupWizardComplete::class,
            ])
            ->authenticatedRoutes(function (): void {
                Route::post('/first-visit/finish', FinishFirstVisitController::class)->name('first-visit.finish');
            })
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE,
                fn (): string => view('filament.components.first-visit-banner')->render(),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => view('filament.components.login-links')->render(),
            );
    }
}
