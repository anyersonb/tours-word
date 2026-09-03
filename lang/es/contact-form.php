<?php

/**
 * Copy owned by backend-laravel for the /contacto submission flow (server
 * validation attributes/messages + the post-submit flash banner). The rest
 * of the page's copy (labels, placeholders, FAQ, etc.) lives in
 * lang/es/site.php, owned by maquetador-frontend — do not merge these.
 */
return [

    'validation' => [
        'attributes' => [
            'name' => 'nombre',
            'email' => 'correo',
            'phone' => 'teléfono',
            'subject' => 'asunto',
            'message' => 'mensaje',
            'privacy' => 'la casilla de aceptación de la política de privacidad',
        ],
        'messages' => [
            'privacy.accepted' => 'Debes aceptar la política de privacidad para enviar el mensaje.',
            'subject.in' => 'Elige un asunto válido de la lista.',
        ],
    ],

    'flash' => [
        'success' => '¡Gracias! Tu mensaje fue enviado correctamente. Te responderemos pronto.',
        'error_summary' => 'Revisa los campos marcados abajo: hay algo que corregir antes de poder enviar el mensaje.',
    ],

    'info' => [
        // New Setting (contact_schedule) added by this fix; the rest of the
        // "Información de contacto" card titles live in site.php.
        'schedule_title' => 'Horario de atención',
    ],

];
