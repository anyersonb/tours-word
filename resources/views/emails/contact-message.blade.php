<x-mail::message>
# Nuevo mensaje de contacto

Llegó un mensaje nuevo desde el formulario de contacto del sitio.

**Nombre:** {{ $contactMessage->name }}
**Correo:** {{ $contactMessage->email }}
@if($contactMessage->phone)
**Teléfono / WhatsApp:** {{ $contactMessage->phone }}
@endif
**Asunto:** {{ __('site.contacto.form.subject_options.'.$contactMessage->subject) }}
@if($contactMessage->tour_title)

**Tour consultado:** {{ $contactMessage->tour_title }}
@endif

**Mensaje:**

{{ $contactMessage->message }}

<x-mail::button :url="url('/admin/contact-messages/'.$contactMessage->id.'/edit')">
Ver en el panel
</x-mail::button>

Recibido el {{ $contactMessage->created_at->translatedFormat('d/m/Y H:i') }} (hora Lima).

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
