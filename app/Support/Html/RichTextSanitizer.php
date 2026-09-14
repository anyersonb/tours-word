<?php

namespace App\Support\Html;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Defecto 1 (auditoria cliente, 2026-09-14): Tour::description is edited
 * through Filament\Forms\Components\RichEditor (see TourForm) and was being
 * rendered with Blade's escaping {{ }} on the public ficha -- the client
 * saw the literal "&lt;p&gt;..." tags instead of a formatted paragraph.
 *
 * Decision taken: description IS meant to carry formatting (the panel
 * already offers a rich text editor for it, and real content had already
 * been typed and saved with it before this fix) -- so the fix is to render
 * it as HTML, not to strip the editor away. But this HTML comes out of the
 * CMS and is rendered on the public site with {!! !!}: a compromised panel
 * account, or a direct write to the database, must never be able to turn
 * into a <script> or an onerror= running in a visitor's browser (the exact
 * class of bug a JSON-LD escaping issue already opened once in this
 * project). Trusting "the editor is a real person" is not a control --
 * sanitizing what gets printed is.
 *
 * The allowlist below is deliberately narrower than everything Filament's
 * RichEditor toolbar CAN produce -- it matches EXACTLY the toolbar buttons
 * enabled on the fields that use this sanitizer (see
 * TourForm::configure()'s ->toolbarButtons() on the "description" fields
 * of Tour and on the itinerary Repeater's "description"): bold/italic/
 * underline/strike, link, h2/h3, blockquote, bullet/ordered lists. No
 * images, tables, alignment or arbitrary attributes. If a toolbar button
 * is ever added there, its tag/attribute must be added here too, or the
 * client's formatting will be silently stripped on the public site.
 */
class RichTextSanitizer
{
    public static function sanitize(?string $html): string
    {
        if (! filled($html)) {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        static $sanitizer = null;

        if ($sanitizer === null) {
            $config = (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('blockquote')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('a', ['href', 'target'])
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer');

            $sanitizer = new HtmlSanitizer($config);
        }

        return $sanitizer;
    }
}
