<?php

namespace Tests\Feature;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\Pages\EditContactMessage;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessageAdminResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_an_admin_can_see_contact_messages_in_the_list(): void
    {
        $message = ContactMessage::factory()->create(['name' => 'Carlos Huamán']);

        $this->actingAs($this->admin());

        Livewire::test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$message]);
    }

    public function test_a_non_admin_is_denied_access_to_the_contact_messages_panel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin/contact-messages')
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login_instead_of_seeing_contact_messages(): void
    {
        $this->get('/admin/contact-messages')->assertRedirect('/admin/login');
    }

    public function test_an_admin_can_mark_a_message_as_atendido_without_altering_its_content(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'Luis Fernández',
            'message' => 'Consulta original del visitante.',
            'status' => ContactMessageStatus::Nuevo,
        ]);

        $this->actingAs($this->admin());

        Livewire::test(EditContactMessage::class, ['record' => $message->getKey()])
            ->fillForm(['status' => ContactMessageStatus::Atendido->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $message->refresh();

        $this->assertTrue($message->status === ContactMessageStatus::Atendido);
        // The visitor's original content must survive an attention-status
        // update: this form disables every other field on purpose.
        $this->assertSame('Luis Fernández', $message->name);
        $this->assertSame('Consulta original del visitante.', $message->message);
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $nuevo = ContactMessage::factory()->create(['status' => ContactMessageStatus::Nuevo]);
        $atendido = ContactMessage::factory()->create(['status' => ContactMessageStatus::Atendido]);

        $this->actingAs($this->admin());

        Livewire::test(ListContactMessages::class)
            ->filterTable('status', ContactMessageStatus::Atendido->value)
            ->assertCanSeeTableRecords([$atendido])
            ->assertCanNotSeeTableRecords([$nuevo]);
    }
}
