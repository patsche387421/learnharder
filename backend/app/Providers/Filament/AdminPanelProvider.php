<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Akzentfarbe Bronze: 600 = --accent hell (#9a5a24), 400 = --accent dunkel (#d4915a).
     * Filaments automatische Palette wird zu grellem Orange, deshalb von Hand in OKLCH (Farbton 58).
     */
    private const BRONZE = [
        50 => 'oklch(0.975 0.015 58)',
        100 => 'oklch(0.945 0.03 58)',
        200 => 'oklch(0.89 0.055 58)',
        300 => 'oklch(0.81 0.085 58)',
        400 => 'oklch(0.712 0.108 59)',
        500 => 'oklch(0.62 0.11 58)',
        600 => 'oklch(0.531 0.108 57)',
        700 => 'oklch(0.46 0.095 57)',
        800 => 'oklch(0.39 0.08 57)',
        900 => 'oklch(0.33 0.065 57)',
        950 => 'oklch(0.24 0.045 57)',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('learnharder')
            // Farben und Schrift aus dem Design-System (frontend/src/styles/tokens.css)
            ->colors([
                'primary' => self::BRONZE,
                'gray' => Color::Stone,
            ])
            ->font('Atkinson Hyperlegible Next', url: asset('fonts/atkinson-hyperlegible-next/index.css'), provider: LocalFontProvider::class)
            // Reihenfolge im Menü: erst die Redaktion, dann die Schulstruktur
            ->navigationGroups(['Inhalte', 'Schule'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
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
            ]);
    }
}
