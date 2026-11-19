<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile()
            ->brandName(config('brand.name'))
            // Logo horizontal oficial (texto oscuro sobre fondo transparente):
            // no hay versión clara, por eso el modo oscuro está desactivado.
            ->brandLogo(asset('images/brand/inka-palace-logo-horizontal.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->darkMode(false)
            ->font('Poppins', url: asset('css/admin-poppins.css'), provider: LocalFontProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                // Rampas construidas a mano con la paleta oficial (tokens.css).
                // Color::hex() en Filament 4 inventa una rampa saturada que NO
                // se parece a la marca. Los tonos 500/600 llevan texto blanco
                // con contraste >= 4.5:1 (AA).
                // Sage #556b52 (5.82:1 con blanco).
                'primary' => [
                    50 => '#f1f5f0', 100 => '#e3ebe2', 200 => '#c9d7c7', 300 => '#a8bda5',
                    400 => '#84a081', 500 => '#5f785c', 600 => '#556b52', 700 => '#475b45',
                    800 => '#3f5340', 900 => '#324232', 950 => '#1f2a20',
                ],
                // Terracota de alerta #ab3a30 (6.21:1 con blanco).
                'danger' => [
                    50 => '#fdf3f1', 100 => '#fbe3df', 200 => '#f6c9c3', 300 => '#eea39a',
                    400 => '#de756a', 500 => '#c04a3e', 600 => '#ab3a30', 700 => '#8d2f27',
                    800 => '#732a24', 900 => '#5f2723', 950 => '#34110f',
                ],
                // Verde de confirmación #158045 (4.99:1 con blanco).
                'success' => [
                    50 => '#effaf3', 100 => '#d8f2e1', 200 => '#b4e4c6', 300 => '#82cf9f',
                    400 => '#4cb375', 500 => '#158045', 600 => '#0f6a38', 700 => '#0d5a30',
                    800 => '#0b4a28', 900 => '#093d21', 950 => '#04220f',
                ],
                // Dorado de marca; tonos de texto en gold-deep #7a5a1f.
                'warning' => [
                    50 => '#fbf6ea', 100 => '#f5eddb', 200 => '#e8d6b0', 300 => '#dac08b',
                    400 => '#c9a96a', 500 => '#8c6a25', 600 => '#7a5a1f', 700 => '#654a19',
                    800 => '#523c16', 900 => '#3e2f24', 950 => '#2b2019',
                ],
                // Neutros cálidos (ivory / espresso de la marca).
                'gray' => [
                    50 => '#faf7ee', 100 => '#f3eee1', 200 => '#e3dac8', 300 => '#d0c4ae',
                    400 => '#a39683', 500 => '#6f5d4e', 600 => '#5c4b3d', 700 => '#4f3d30',
                    800 => '#3e2f24', 900 => '#2b2019', 950 => '#1c140f',
                ],
            ])
            // Orden del menú lateral, en español llano. Lo que más mira la
            // clienta (mensajes) va arriba.
            ->navigationGroups([
                'Mensajes',
                'Catálogo',
                'Nosotros',
                'Ajustes',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // Widgets del Inicio: se descubren desde app/Filament/Widgets
            // (bienvenida, cifras y últimos mensajes). Sin widgets de ejemplo.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
