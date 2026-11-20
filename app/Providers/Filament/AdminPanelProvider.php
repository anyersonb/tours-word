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
                // Rampas construidas a mano con la paleta del PDF (tokens.css;
                // decisión de Anyerson 2026-11-19: mandan los colores del PDF).
                // Color::hex() en Filament 4 inventa una rampa saturada que NO
                // se parece a la marca. Los tonos 500/600 llevan texto blanco
                // con contraste >= 4.5:1 (AA).
                // Verde bosque del PDF #24483a (10.18:1 con blanco). El dorado
                // no sirve de primario: Filament pone texto blanco (2.17:1).
                'primary' => [
                    50 => '#eef4f1', 100 => '#d6e4dc', 200 => '#adc9b9', 300 => '#7fa892',
                    400 => '#4f8169', 500 => '#2f5c4a', 600 => '#24483a', 700 => '#1b3a2e',
                    800 => '#163026', 900 => '#11261e', 950 => '#0a1712',
                ],
                // Rojo del PDF #9e2b1d (7.45:1 con blanco).
                'danger' => [
                    50 => '#fdf3f1', 100 => '#f9e3df', 200 => '#f2c7c0', 300 => '#e59a8e',
                    400 => '#d4614f', 500 => '#b8382a', 600 => '#9e2b1d', 700 => '#84241a',
                    800 => '#6b1f17', 900 => '#561b15', 950 => '#2f0d09',
                ],
                // Verde de confirmación #158045 (4.99:1 con blanco).
                'success' => [
                    50 => '#effaf3', 100 => '#d8f2e1', 200 => '#b4e4c6', 300 => '#82cf9f',
                    400 => '#4cb375', 500 => '#158045', 600 => '#0f6a38', 700 => '#0d5a30',
                    800 => '#0b4a28', 900 => '#093d21', 950 => '#04220f',
                ],
                // Dorado del PDF #e0a63a; tonos de texto en gold-deep #8c5f10 (5.59:1).
                'warning' => [
                    50 => '#fdf6e7', 100 => '#faebc8', 200 => '#f3d895', 300 => '#ebc265',
                    400 => '#e0a63a', 500 => '#a87418', 600 => '#8c5f10', 700 => '#74500f',
                    800 => '#5e410e', 900 => '#4a340d', 950 => '#2b1d06',
                ],
                // Neutros cálidos del PDF (crema #f7f4ef / café #291b12).
                'gray' => [
                    50 => '#f7f4ef', 100 => '#efe9e0', 200 => '#ded4c6', 300 => '#c7b9a6',
                    400 => '#9c8b77', 500 => '#6e5c4c', 600 => '#58483b', 700 => '#45372c',
                    800 => '#34261b', 900 => '#291b12', 950 => '#1a110b',
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
