<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Models\TourImage;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_responds_200_and_shows_the_brand(): void
    {
        $this->get(route('home', ['locale' => 'es']))
            ->assertOk()
            ->assertSee('Inka Palace Travel');

        $this->assertSame('Inka Palace Travel', config('brand.name'));
    }

    public function test_home_with_empty_cms_delivers_empty_collections_not_invented_data(): void
    {
        $response = $this->get(route('home', ['locale' => 'es']))->assertOk();

        $response->assertViewHas('destinations', fn ($v) => $v->isEmpty());
        $response->assertViewHas('experiences', fn ($v) => $v->isEmpty());
        $response->assertViewHas('featuredTours', fn ($v) => $v->isEmpty());
        $response->assertViewHas('planTours', fn ($v) => $v->isEmpty());
    }

    public function test_unpublished_content_is_never_shown(): void
    {
        $destination = Destination::factory()->create(['is_published' => false, 'name' => ['es' => 'Destino Oculto Zeta']]);
        Experience::factory()->create(['is_published' => false, 'name' => ['es' => 'Experiencia Oculta Zeta']]);
        Tour::factory()->create([
            'destination_id' => $destination->id,
            'is_featured' => true,
            'is_published' => false,
            'title' => ['es' => 'Tour Oculto Zeta'],
        ]);

        $response = $this->get(route('home', ['locale' => 'es']))->assertOk();

        $response->assertDontSee('Tour Oculto Zeta');
        $response->assertDontSee('Destino Oculto Zeta');
        $response->assertDontSee('Experiencia Oculta Zeta');
        $response->assertViewHas('featuredTours', fn ($v) => $v->isEmpty());
        $response->assertViewHas('destinations', fn ($v) => $v->isEmpty());
        $response->assertViewHas('experiences', fn ($v) => $v->isEmpty());
    }

    public function test_featured_tours_use_money_for_prices_and_are_limited_to_four(): void
    {
        $destination = Destination::factory()->create(['name' => ['es' => 'Cusco'], 'slug' => ['es' => 'cusco']]);

        foreach (range(1, 5) as $i) {
            $tour = Tour::factory()->create([
                'destination_id' => $destination->id,
                'is_featured' => true,
                'order' => $i,
                'duration_label' => ['es' => '1 día'],
                'price_pen_cents' => 12050 * $i,
            ]);
        }

        // Un tour publicado pero no destacado no debe aparecer.
        Tour::factory()->create(['is_featured' => false, 'title' => ['es' => 'No Destacado Zeta']]);

        $response = $this->get(route('home', ['locale' => 'es']))->assertOk();

        $response->assertViewHas('featuredTours', function ($tours) {
            return $tours->count() === 4
                && $tours->first()['price'] === Money::pen(12050)->format()
                && $tours->first()['destination_name'] === 'Cusco'
                && $tours->first()['duration_label'] === '1 día';
        });
        // planTours incluye todos los publicados: se acota al dato de destacados.
        $response->assertViewHas('featuredTours', fn ($tours) => ! $tours->pluck('title')->contains('No Destacado Zeta'));
    }

    public function test_inca_trail_price_is_formatted_with_money_from_config(): void
    {
        $this->get(route('home', ['locale' => 'es']))->assertViewHas('incaTrailPrice', 'S/ 3,500.00');
    }

    public function test_inca_trail_strip_uses_the_same_usd_as_the_4plus_days_card(): void
    {
        $tour = Tour::factory()->create([
            'duration_label' => ['es' => '4 días'],
            'price_pen_cents' => 35625,
            'price_usd_cents' => 9500,
        ]);

        $this->get(route('home', ['locale' => 'es']))
            ->assertViewHas('planTours', fn ($t) => $t->firstWhere('bucket', '4mas')['price_usd_cents'] === 9500)
            ->assertViewHas('incaTrailPriceUsd', Money::usd(9500)->format())
            ->assertViewHas('incaTrailPrice', Money::pen(35625)->format());
    }

    public function test_inca_trail_strip_converts_the_card_pen_price_when_tour_has_no_usd(): void
    {
        \App\Models\Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, '3.75');
        Tour::factory()->create([
            'duration_label' => ['es' => '4 días'],
            'price_pen_cents' => 37500,
            'price_usd_cents' => 0,
        ]);

        $this->get(route('home', ['locale' => 'es']))
            ->assertViewHas('incaTrailPriceUsd', Money::usd(10000)->format());
    }

    public function test_tour_without_price_has_null_price_and_first_image_is_used(): void
    {
        $tour = Tour::factory()->create(['is_featured' => true, 'price_pen_cents' => 0]);
        TourImage::factory()->create(['tour_id' => $tour->id, 'path' => 'tours/b.jpg', 'order' => 2]);
        TourImage::factory()->create(['tour_id' => $tour->id, 'path' => 'tours/a.jpg', 'order' => 1]);

        $this->get(route('home', ['locale' => 'es']))->assertViewHas('featuredTours', function ($tours) {
            $card = $tours->first();

            return $card['price'] === null && str_ends_with((string) $card['image'], 'tours/a.jpg');
        });
    }

    public function test_destinations_count_only_published_tours_and_map_images_by_slug(): void
    {
        $lima = Destination::factory()->create(['name' => ['es' => 'Lima'], 'slug' => ['es' => 'lima'], 'description' => ['es' => null]]);
        $otro = Destination::factory()->create(['name' => ['es' => 'Sin Foto'], 'slug' => ['es' => 'sin-foto-zeta']]);
        Tour::factory()->count(2)->create(['destination_id' => $lima->id, 'is_published' => true]);
        Tour::factory()->create(['destination_id' => $lima->id, 'is_published' => false]);

        $this->get(route('home', ['locale' => 'es']))->assertViewHas('destinations', function ($items) {
            $lima = $items->firstWhere('slug', 'lima');
            $otro = $items->firstWhere('slug', 'sin-foto-zeta');

            return $lima['tours_count'] === 2
                && $lima['image'] === asset('img/provisional/lima.jpg')
                && $lima['tagline'] === 'Ciudad y gastronomía'
                && $lima['city_tagline'] === 'Ciudad y gastronomía'
                && $otro['image'] === null;
        });
    }

    public function test_plan_tours_are_grouped_by_duration_and_empty_when_not_inferable(): void
    {
        Tour::factory()->create(['duration_label' => ['es' => 'Medio día'], 'order' => 1]);
        Tour::factory()->create(['duration_label' => ['es' => '4 horas'], 'order' => 2]);
        Tour::factory()->create(['duration_label' => ['es' => '2 días / 1 noche'], 'order' => 3]);
        Tour::factory()->create(['duration_label' => ['es' => '4 días'], 'order' => 4]);

        $this->get(route('home', ['locale' => 'es']))->assertViewHas('planTours', function ($tours) {
            return $tours->pluck('bucket')->all() === ['4h', '2-3dias', '4mas'];
        });
    }

    public function test_hero_has_three_slides_and_placeholders_are_visible_markers(): void
    {
        $this->get(route('home', ['locale' => 'es']))->assertViewHas('hero', fn ($hero) => count($hero) === 3
            && $hero[0]['slug'] === 'lima'
            && $hero[2]['slug'] === 'machu-picchu');

        // Sin cifras ni reseñas inventadas: los sellos y testimonios son marcadores.
        foreach (__('home.testimonials.items') as $item) {
            $this->assertStringContainsString('[Nombre del viajero]', $item['name']);
        }
        foreach (__('home.testimonials.seals') as $seal) {
            $this->assertStringContainsString('[4,9]', $seal['text']);
        }
        $this->assertSame('[N]+', __('home.landscapes.stats.0.value'));
        $this->assertCount(6, __('home.faq.items'));
    }

    public function test_root_redirects_301_to_the_spanish_home(): void
    {
        $this->get('/')->assertStatus(301)->assertRedirect('/es/');
    }

    public function test_home_is_served_under_both_active_locale_prefixes(): void
    {
        $this->get('/es/')->assertOk();
        $this->get('/en/')->assertOk();
        $this->assertStringEndsWith('/es', route('home', ['locale' => 'es']));
    }

    public function test_cards_link_to_real_localized_urls_per_locale(): void
    {
        $destination = Destination::factory()->create([
            'name' => ['es' => 'Cusco', 'en' => 'Cusco'],
            'slug' => ['es' => 'cusco', 'en' => 'cusco-city'],
        ]);
        $experience = Experience::factory()->create(['slug' => ['es' => 'trekking', 'en' => 'hiking']]);
        Tour::factory()->create([
            'destination_id' => $destination->id,
            'is_featured' => true,
            'slug' => ['es' => 'ruta-es', 'en' => 'route-en'],
        ]);

        $this->get('/es/')->assertViewHas('destinations', fn ($d) => str_ends_with($d->first()['url'], '/es/destinos/cusco'))
            ->assertViewHas('experiences', fn ($e) => str_ends_with($e->first()['url'], '/es/experiencias/trekking'))
            ->assertViewHas('featuredTours', fn ($t) => str_ends_with($t->first()['url'], '/es/tours/ruta-es'));

        $this->get('/en/')->assertViewHas('destinations', fn ($d) => str_ends_with($d->first()['url'], '/en/destinos/cusco-city') && $d->first()['key'] === 'cusco')
            ->assertViewHas('experiences', fn ($e) => str_ends_with($e->first()['url'], '/en/experiencias/hiking'))
            ->assertViewHas('featuredTours', fn ($t) => str_ends_with($t->first()['url'], '/en/tours/route-en'));
    }

    public function test_hero_ctas_point_to_real_routes_and_images_use_asset(): void
    {
        $this->get('/es/')->assertViewHas('hero', function ($hero) {
            return $hero[0]['url_primary'] === route('tours.index', ['locale' => 'es'])
                && $hero[0]['url_secondary'] === route('destinations.index', ['locale' => 'es'])
                && $hero[0]['image'] === asset('img/provisional/hero-lima.jpg')
                && ! str_starts_with($hero[0]['image'], '/');
        });
    }

    public function test_cms_cover_image_wins_over_the_provisional_brand_image(): void
    {
        Destination::factory()->create([
            'slug' => ['es' => 'lima'],
            'cover_image_path' => 'destinations/lima-real.jpg',
        ]);

        $this->get('/es/')->assertViewHas('destinations', fn ($d) => str_ends_with((string) $d->first()['image'], 'destinations/lima-real.jpg'));
    }

    public function test_home_lang_files_have_the_same_keys_in_both_locales(): void
    {
        $this->assertSame(
            array_keys(\Illuminate\Support\Arr::dot(require lang_path('es/home.php'))),
            array_keys(\Illuminate\Support\Arr::dot(require lang_path('en/home.php')))
        );
    }
}
