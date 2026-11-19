<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Enlace de WhatsApp con mensaje prellenado (CRO A-2). El número sale de
 * Configuración (CMS) si la clienta lo cargó; si no, de
 * config('brand.contact.whatsapp_url') -- la misma cascada del header y el
 * footer.
 */
class WhatsApp
{
    /** Teléfono visible: Configuración (CMS) y, si está vacío, config(brand.contact.phone). */
    public static function phone(): string
    {
        return (string) (Setting::get('contact_phone') ?: config('brand.contact.phone'));
    }

    public static function baseUrl(): string
    {
        $phone = Setting::get('contact_phone');

        return filled($phone)
            ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $phone)
            : (string) config('brand.contact.whatsapp_url');
    }

    public static function url(?string $text = null): string
    {
        $base = self::baseUrl();

        return filled($text) ? $base.'?text='.rawurlencode($text) : $base;
    }
}
