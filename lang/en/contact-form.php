<?php

/**
 * Copy owned by backend-laravel for the /contact submission flow (server
 * validation attributes/messages + the post-submit flash banner). The rest
 * of the page's copy (labels, placeholders, FAQ, etc.) lives in
 * lang/en/site.php, owned by maquetador-frontend — do not merge these.
 */
return [

    'validation' => [
        'attributes' => [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'subject' => 'subject',
            'message' => 'message',
            'privacy' => 'the privacy policy acceptance checkbox',
        ],
        'messages' => [
            'privacy.accepted' => 'You must accept the privacy policy to send the message.',
            'subject.in' => 'Choose a valid subject from the list.',
        ],
    ],

    'flash' => [
        'success' => 'Thank you! Your message was sent successfully. We will get back to you soon.',
        'error_summary' => 'Check the fields marked below: there is something to fix before we can send the message.',
    ],

    'info' => [
        // New Setting (contact_schedule) added by this fix; the rest of the
        // "Contact information" card titles live in site.php.
        'schedule_title' => 'Business hours',
    ],

];
