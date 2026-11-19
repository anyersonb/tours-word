<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Humo del panel /admin tras el rediseño "look & orden": login con marca,
 * Inicio con widgets propios, perfil y cada recurso responden 200 con una
 * sesión de admin y en español.
 */
class AdminPanelLookAndOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_branded_and_in_spanish(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Bienvenida a '.config('brand.name'))
            ->assertSee('Ingresa con tu correo y tu clave')
            ->assertSee('admin-poppins.css', false)
            ->assertDontSee('Sign in');
    }

    public function test_dashboard_shows_custom_widgets_and_no_filament_info_widget(): void
    {
        ContactMessage::factory()->create(['name' => 'Visitante Prueba']);
        Tour::factory()->create(['is_published' => true]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Nuevo tour')
            ->assertSee('Ver el sitio')
            ->assertSee('Tours publicados')
            ->assertSee('Últimos mensajes de contacto')
            ->assertSee('Visitante Prueba')
            ->assertSeeInOrder(['Inicio', 'Mensajes', 'Catálogo', 'Nosotros', 'Ajustes'])
            ->assertDontSee('Escritorio');
    }

    public function test_every_admin_page_renders_for_an_admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Setting::query()->count();

        $tour = Tour::factory()->create();
        $destination = Destination::factory()->create();
        $experience = Experience::factory()->create();
        $member = TeamMember::factory()->create();
        $message = ContactMessage::factory()->create();

        foreach ([
            '/admin/profile',
            '/admin/configuracion',
            '/admin/tours',
            '/admin/tours/create',
            "/admin/tours/{$tour->getKey()}/edit",
            '/admin/destinations',
            '/admin/destinations/create',
            "/admin/destinations/{$destination->getKey()}/edit",
            '/admin/experiences',
            '/admin/experiences/create',
            "/admin/experiences/{$experience->getKey()}/edit",
            '/admin/team-members',
            '/admin/team-members/create',
            "/admin/team-members/{$member->getKey()}/edit",
            '/admin/contact-messages',
            "/admin/contact-messages/{$message->getKey()}/edit",
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
