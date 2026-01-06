<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\PaymentChartWidget;
use App\Livewire\ActivityLogTableWidget;
use App\Livewire\PaymentStatsWidget;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Enums\Width;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
  public function panel(Panel $panel): Panel
  {
    return $panel
      ->default()
      ->brandName(fn() => getSetting('site_name'))
      ->spa()
      ->id('admin')
      ->path('admin')
      ->login()
      ->registration(false)
      ->profile()
      ->passwordReset()
      ->multiFactorAuthentication([
        AppAuthentication::make(),
        EmailAuthentication::make()
          ->codeExpiryMinutes(10),
      ])
      ->favicon(asset('favicon.png'))
      ->databaseNotifications()
      ->sidebarCollapsibleOnDesktop()
      ->maxContentWidth(Width::Full)
      ->emailVerification()
      ->when(
        config('app.env') !== 'local',
        fn($panel) => $panel->unsavedChangesAlerts()
      )
      ->colors(function () {
        $color = getSetting('site_theme', 'Cyan');
        return [
          'primary' => constant(Color::class . '::' . $color)
        ];
      })
      ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
      ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
      ->pages([
        Dashboard::class,
      ])
      ->topbar(false)
      ->navigationGroups([
        'Payments',
        'Productivity',
        'Emails',
        'Items',
        'File Manager',
        'Blog',
        'Settings',
        'Logs',
      ])
      ->widgets([
        PaymentStatsWidget::class,
        PaymentChartWidget::class,
        ActivityLogTableWidget::class,
      ])
      ->middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
      ])
      ->authMiddleware([
        Authenticate::class,
      ])
      ->renderHook(
        PanelsRenderHook::HEAD_END,
        fn(): HtmlString => new HtmlString('
          <style>
            *::-webkit-scrollbar { display: none; }
            * { -ms-overflow-style: none; scrollbar-width: none; }
          </style>
        ')
      );
  }
}
