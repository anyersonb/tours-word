<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Login del panel con textos en español y la marca del cliente. Se registra
 * con ->login(Login::class) en AdminPanelProvider; vive fuera de
 * app/Filament/Pages para que discoverPages() no la registre por duplicado.
 */
class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Ingresar al panel';
    }

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Bienvenida a '.config('brand.name');
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Ingresa con tu correo y tu clave para administrar tus tours, destinos y mensajes.';
    }
}
