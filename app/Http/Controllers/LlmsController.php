<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Response;

/**
 * CRO M-5: /llms.txt (descubribilidad para asistentes de IA). Texto plano
 * con la marca, qué ofrece el sitio, contacto y las URLs principales. Sin
 * cifras ni afirmaciones: solo lo que ya es público en el sitio. Los hosts
 * salen de la request (route()/url()), nunca cableados.
 */
class LlmsController extends Controller
{
    public function __invoke(): Response
    {
        $phone = Setting::get('contact_phone') ?: config('brand.contact.phone');
        $email = Setting::get('contact_email') ?: config('brand.contact.email');
        $address = Setting::get('contact_address') ?: config('brand.contact.address');

        $lines = [
            '# '.config('brand.name'),
            '',
            '> '.__('site.seo.default_description', [], 'es'),
            '',
            '## Secciones',
        ];

        foreach (['es' => 'Español', 'en' => 'English'] as $locale => $label) {
            if (! in_array($locale, config('cms.active_locales', []), true)) {
                continue;
            }
            $lines[] = '- ['.$label.' · Inicio]('.route('home', ['locale' => $locale]).')';
            $lines[] = '- ['.$label.' · Tours]('.route('tours.index', ['locale' => $locale]).')';
            $lines[] = '- ['.$label.' · Destinos]('.route('destinations.index', ['locale' => $locale]).')';
            $lines[] = '- ['.$label.' · Experiencias]('.route('experiences.index', ['locale' => $locale]).')';
            $lines[] = '- ['.$label.' · Contacto]('.route('contact', ['locale' => $locale]).')';
        }

        $lines[] = '';
        $lines[] = '## Contacto';
        foreach (array_filter([$phone ? 'Teléfono/WhatsApp: '.$phone : null, $email ? 'Email: '.$email : null, $address ? 'Dirección: '.$address : null]) as $l) {
            $lines[] = '- '.$l;
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
