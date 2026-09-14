<?php

return [

    // SEO (lote 1, arreglo A3). Fallback description when a view doesn't pass
    // its own `description` to <x-layout> (today that's contact.blade.php,
    // out of scope for this lote). No figures or regulatory claims: see
    // docs/lote-1/00-sistema-diseno.md.
    'seo' => [
        'default_description' => 'Pacha Viva is a tour operator based in Cusco, Peru. We design authentic tours and experiences across the country with local experts.',
    ],

    // Shared UI (lote 1, arreglo A1 on `carousel-shell.blade.php`).
    // ':position'/':total' are substituted client-side (Alpine), not here:
    // they react to the active slide and the real card count.
    'ui' => [
        'carousel' => [
            'pagination_group' => ':label pagination',
            'go_to_card' => 'Go to card :position of :total',
        ],

        // Catalog pagination and detail gallery (lote 3, x-ui.pagination and
        // x-ui.gallery components, new).
        'pagination' => [
            'nav_label' => 'Pagination',
            'previous' => 'Previous',
            'next' => 'Next',
            'page_of' => 'Page :current of :last',
        ],

        'gallery' => [
            'nav_label' => ':title photo gallery',
            'thumbnails_group' => ':title thumbnails',
            'show_photo' => 'Show photo :position of :total',
        ],

        // R (lote 3): x-ui.tour-card had "Ver tour" and the "Desde" prefix
        // hardcoded since lote 1 — translated here because the new catalog
        // triples its use and activating EN can't depend on remembering this
        // component.
        'tour_card' => [
            'cta' => 'View tour',
            'price_prefix' => 'From',
        ],

        // Objetivo 2 (lote i18n, 2026-09-14): honest fallback notice when a
        // catalog entry (tour/destination/experience) doesn't have its
        // :language translation yet -- never pretend the content is in that
        // language. See ResolvesBySlugByLocale.
        'content_fallback_notice' => 'This content isn\'t translated into :language yet — showing the original version.',
    ],

    'nav' => [
        'home' => 'Home',
        'tours' => 'Tours',
        'destinations' => 'Destinations',
        'experiences' => 'Experiences',
        'about' => 'About us',
        'contact' => 'Contact',
    ],

    'header' => [
        'search' => 'Search',
        'search_soon' => 'Search — coming soon',
        'currency' => 'Currency',
        'language' => 'Language',
        'language_soon' => 'Coming soon',
        'contact_cta' => 'Contact us',
        'open_menu' => 'Open menu',
        'close_menu' => 'Close menu',
    ],

    'footer' => [
        'quick_links' => 'Quick links',
        'destinations' => 'Destinations',
        'information' => 'Information',
        'contact' => 'Contact',
        'privacy_policy' => 'Privacy policy',
        'cancellation_policy' => 'Cancellation policy',
        'complaints_book' => 'Complaints book',
        'faq' => 'Frequently asked questions',
        'rights' => 'All rights reserved.',
    ],

    // Home (lote 1, etapa B). Generic marketing copy, no figures or reviews:
    // those come from Setting/DB (see home.blade.php), not from here.
    'home' => [
        // S-12 (SEO audit 02/09): Home's title can't fall back to
        // layout.blade.php's brand-only fallback — it's the site's highest
        // value title. Final copy to be validated with the client/Anyerson.
        'meta' => [
            'title' => 'Tour operator in Cusco, Peru',
            'description' => 'Tour operator in Cusco, Peru. We design authentic, unforgettable experiences in the country\'s most amazing destinations, guided by local experts.',
        ],

        'hero' => [
            'title_before' => 'Experience the best',
            'title_highlight' => 'of Peru',
            'title_after' => 'with local experts',
            'subtitle' => 'We design authentic, unforgettable experiences in Peru\'s most amazing destinations.',
            'cta_primary' => 'Explore tours',
            'cta_secondary' => 'View destinations',
            'photo_alt' => 'Traveler taking in the Andean landscape',
            'trust' => [
                'safe' => '100% safe travel',
                'guides' => 'Expert local guides',
                'personalized' => 'Personalized attention',
                'prices' => 'Best price guarantee',
                'sustainable' => 'Sustainable tourism',
            ],
        ],

        'featured_tours' => [
            'title' => 'Featured tours',
            'cta' => 'View all tours',
        ],

        'destinations' => [
            'title' => 'Must-see destinations',
            'cta' => 'View all',
        ],

        'why_us' => [
            'title_before' => 'Why travel with',
            'title_highlight' => 'us',
            'title_after' => '?',
            'photo_alt' => 'Couple of travelers looking at a map',
            'features' => [
                ['title' => 'Local experts', 'description' => 'We know every corner of Peru.'],
                ['title' => 'Personalized attention', 'description' => 'We help you plan your ideal trip.'],
                ['title' => 'Safe travel', 'description' => 'Your safety and wellbeing are our priority.'],
                ['title' => 'Best experiences', 'description' => 'We create memories that last a lifetime.'],
            ],
            'assistance_title' => '24/7 assistance',
            'assistance_description' => 'We are with you before, during and after your trip.',
        ],

        'experiences' => [
            'title' => 'Unique experiences',
            'cta' => 'View all',
        ],

        'newsletter' => [
            'title' => 'Get offers and news',
            'description' => 'Subscribe to our newsletter and get exclusive deals for your next trip.',
            'photo_alt' => 'Traveler with open arms facing the mountain',
            'email_label' => 'Email address',
            'email_placeholder' => 'Enter your email address',
            'submit' => 'Subscribe',
            'unavailable' => 'Newsletter — coming soon, no real submission yet',
        ],

        'empty' => [
            'tours' => 'You\'ll find our featured tours here very soon.',
            'destinations' => 'You\'ll find our destinations here very soon.',
            'experiences' => 'You\'ll find our experiences here very soon.',
        ],
    ],

    // Contact (lote 1, etapa C). No hardcoded contact figures/data: phone,
    // email, address and social links come from Setting (see
    // contact.blade.php). The form doesn't persist (contact_messages is
    // lote 3): submission disabled, same pattern as the Home newsletter.
    'contacto' => [
        'breadcrumb' => [
            'home' => 'Home',
            'current' => 'Contact',
        ],

        'hero' => [
            'eyebrow' => 'We\'re here to help',
            'title_before' => 'Let\'s talk about your next',
            'title_highlight' => 'adventure',
            'subtitle' => 'Have questions about our tours or destinations? Our team of local experts is ready to help you plan an unforgettable experience in Peru.',
            'photo_alt' => 'Traveler taking in the Andean landscape',
            'attributes' => [
                ['title' => 'Fast response', 'description' => 'We reply in under 24 hrs.'],
                ['title' => 'Personalized guidance', 'description' => 'We help you build the trip that\'s right for you.'],
                ['title' => 'Secure booking', 'description' => 'Your information is protected.'],
            ],
        ],

        'form' => [
            'title' => 'Send us a message',
            'description' => 'Fill out the form and we\'ll get back to you as soon as possible.',
            'name_label' => 'Full name',
            'name_placeholder' => 'Enter your name',
            'email_label' => 'Email address',
            'email_placeholder' => 'Enter your email',
            'phone_label' => 'Phone / WhatsApp',
            'phone_placeholder' => 'E.g. +51 987 654 321',
            'subject_label' => 'Subject',
            'subject_placeholder' => 'Select a subject',
            'subject_options' => [
                'reserva' => 'Tour booking',
                'consulta' => 'General inquiry',
                'modificacion' => 'Modify a booking',
                'otro' => 'Other',
            ],
            'message_label' => 'Message',
            'message_placeholder' => 'Tell us how we can help you...',
            'privacy_pre' => 'I accept the',
            'privacy_link' => 'privacy policy',
            'privacy_pending' => 'privacy policy (being drafted by the client)',
            'privacy_post' => 'and the processing of my data.',
            'submit' => 'Send message',
            'unavailable' => 'Contact form — coming soon, no real submission yet',
        ],

        'info' => [
            'title' => 'Contact information',
            'phone_title' => 'Phone / WhatsApp',
            'email_title' => 'Email address',
            'address_title' => 'Address',
            'social_title' => 'Follow us',
            'social_instagram' => 'Follow us on Instagram',
            'social_facebook' => 'Follow us on Facebook',
            'social_youtube' => 'Follow us on YouTube',
            'social_whatsapp' => 'Message us on WhatsApp',
            'empty' => 'We haven\'t set up any public contact details yet. Check back soon.',
        ],

        'faq' => [
            'title' => 'Frequently asked questions',
            'items' => [
                [
                    'question' => 'How can I book a tour?',
                    'answer' => 'Message us on WhatsApp or by email with the tour and dates you\'re interested in; our team will confirm availability and guide you through payment.',
                ],
                [
                    'question' => 'What payment methods do you accept?',
                    'answer' => 'We accept bank transfer, card and online payment. We\'ll confirm the available options when coordinating your booking.',
                ],
                [
                    'question' => 'Can I customize a tour?',
                    'answer' => 'Yes. Tell us what you\'re looking for and we\'ll tailor the itinerary, duration or group size to fit you.',
                ],
                [
                    'question' => 'What\'s included in the tour price?',
                    'answer' => 'Each tour lists what it includes on its own page (transport, guide, entrance fees, etc.). Check the tour page or ask us directly.',
                ],
                [
                    'question' => 'What is your cancellation policy?',
                    'answer' => 'We haven\'t published this policy yet — the client needs to draft and approve it before online bookings are enabled. In the meantime, contact us directly if you need to cancel or reschedule.',
                ],
            ],
        ],

        'help' => [
            'title' => 'Need immediate help?',
            'description' => 'Message us on WhatsApp and chat with our team in real time.',
            'cta' => 'Chat on WhatsApp',
        ],

        'map' => [
            'title' => 'Where are we?',
            'placeholder_alt' => 'Placeholder map — pending real address',
            'visit_us' => 'Visit us at our office',
            'cta' => 'View on Google Maps',
            'cta_new_tab' => '(opens in a new tab)',
            'missing' => 'We haven\'t set up an address yet. As soon as the client confirms it, you\'ll be able to see how to get here.',
        ],
    ],

    // About us (lote 1, etapa D). D3: the "purpose" and "values" copy is
    // plausible but was written by a generator — it stays as starter content
    // PENDING CLIENT APPROVAL (see docs/lote-1/00-sistema-diseno.md §12). Two
    // specific claims (community/environmental impact, "sustainability") are
    // verifiable statements about the company, not filler adjectives: flagged
    // below. Team figures and stats do NOT live here — they come from
    // TeamMember/Setting, never from this file (D1/D2).
    'nosotros' => [
        'meta' => [
            'description' => 'Pacha Viva is a tour operator based in Cusco, Peru, connecting travelers with the authentic side of the country through local experts.',
        ],

        'breadcrumb' => [
            'home' => 'Home',
            'current' => 'About us',
        ],

        'hero' => [
            'title' => 'About us',
            'tagline' => 'Connecting travelers with the authentic side of Peru',
            // PENDING client: claims "positive impact on local communities
            // and the environment" — verifiable, not filler.
            'description' => 'We are a team passionate about Peru, committed to delivering authentic, responsible and memorable experiences that create a positive impact on local communities and the environment.',
            'photo_alt' => 'Group of travelers celebrating with arms raised at the Rainbow Mountain',
        ],

        'purpose' => [
            'title' => 'Our purpose',
            // PENDING client (starter copy, not drafted by the client yet).
            'paragraph_1' => 'To connect travelers with the essence of Peru through unique, sustainable experiences guided by local experts who know and love their land.',
            // PENDING client: claims "positive impact on communities and the
            // environment" — verifiable, not filler.
            'paragraph_2' => 'We believe in responsible tourism that creates a positive impact on communities and the environment.',
            'signature' => 'Pacha Viva Team',
            'photo_alt' => 'Aerial view of the circular terraces of Moray at sunset',
        ],

        'values' => [
            'title' => 'Our values',
            // PENDING client (starter copy). "Sustainability" promises care
            // for the environment and local cultures: verifiable, not a
            // filler adjective.
            'items' => [
                ['title' => 'Authenticity', 'description' => 'We offer real, authentic experiences.'],
                ['title' => 'Sustainability', 'description' => 'We promote care for the environment and local cultures.'],
                ['title' => 'Quality', 'description' => 'We provide high-quality services with personalized attention.'],
                ['title' => 'Passion', 'description' => 'We love what we do and share it on every trip.'],
            ],
        ],

        'team' => [
            'title' => 'Our team',
            'description' => 'We have expert local guides, tourism professionals and a team committed to making your trip an unforgettable experience.',
        ],

        'cta' => [
            'title' => 'Ready to have the best experience in Peru?',
            'description' => 'Let us be part of your next adventure.',
            'button' => 'Explore tours',
            'photo_alt' => 'Traveler looking at Machu Picchu from the viewpoint at sunrise',
        ],
    ],

    // Tour catalog and detail page (lote 3, pure markup over
    // App\Support\CatalogFixtures). No tour name/description lives here: it's
    // catalog content, not interface copy, and it's written by the client
    // from the CMS — see the note in CatalogFixtures.
    'tours' => [
        'index' => [
            'meta' => [
                'title' => 'Tours in Peru',
                'description' => 'Discover all our tours across Peru. Filter by destination or experience and find the perfect trip for you.',
            ],
            'breadcrumb' => [
                'home' => 'Home',
                'current' => 'Tours',
            ],
            'hero' => [
                'title' => 'Our tours',
                'subtitle' => 'Explore all our tours across Peru and filter by destination or experience to find the perfect trip for you.',
            ],
            'filters' => [
                'destination_label' => 'Destination',
                'destination_placeholder' => 'All destinations',
                'experience_label' => 'Experience',
                'experience_placeholder' => 'All experiences',
                'submit' => 'Filter',
                'clear' => 'Clear filters',
            ],
            'empty' => 'We couldn\'t find any tours with those filters. Try removing one.',
        ],

        'show' => [
            'breadcrumb_index' => 'Tours',
            'price_prefix' => 'From',
            'cta_reserve' => 'Book this tour',
            'duration_label' => 'Duration',
            'difficulty_label' => 'Difficulty',
            'meeting_point_title' => 'Meeting point',
            'itinerary_title' => 'Itinerary',
            'inclusions_title' => 'Included',
            'exclusions_title' => 'Not included',
            'cta_banner_title' => 'Want to see more tours in :destination?',
            'cta_banner_button' => 'View more tours',
        ],
    ],

    // Destination catalog and detail page (lote 3). Same pattern as 'tours'.
    'destinations' => [
        'index' => [
            'meta' => [
                'title' => 'Destinations in Peru',
                'description' => 'Discover the destinations we have for you in Peru.',
            ],
            'breadcrumb' => [
                'home' => 'Home',
                'current' => 'Destinations',
            ],
            'hero' => [
                'title' => 'Destinations',
                'subtitle' => 'Explore the destinations we have for you in Peru.',
            ],
            'empty' => 'You\'ll find all our destinations here very soon.',
        ],

        'show' => [
            'breadcrumb_index' => 'Destinations',
            'related_tours_title' => 'Tours in :destination',
            'related_tours_empty' => 'We haven\'t published any tours in this destination yet. Check back soon.',
            'cta_all_tours' => 'View all tours',
        ],
    ],

    // Experience catalog and detail page (lote 3). Same pattern as 'tours'.
    'experiences' => [
        'index' => [
            'meta' => [
                'title' => 'Experiences in Peru',
                'description' => 'Discover the experiences we have for you in Peru.',
            ],
            'breadcrumb' => [
                'home' => 'Home',
                'current' => 'Experiences',
            ],
            'hero' => [
                'title' => 'Experiences',
                'subtitle' => 'Explore the experiences we have for you in Peru.',
            ],
            'empty' => 'You\'ll find all our experiences here very soon.',
        ],

        'show' => [
            'breadcrumb_index' => 'Experiences',
            'related_tours_title' => ':experience tours',
            'related_tours_empty' => 'We haven\'t published any tours for this experience yet. Check back soon.',
            'cta_all_tours' => 'View all tours',
        ],
    ],

];
