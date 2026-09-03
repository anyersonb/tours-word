<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Server-side validation for the public /contacto form. The honeypot field
 * ("website") is intentionally NOT validated here — a filled honeypot is
 * handled in the controller by silently pretending success, never by
 * surfacing a validation error that would tip off a bot. See
 * App\Http\Controllers\ContactMessageController::store().
 */
class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', Rule::in(array_keys(__('site.contacto.form.subject_options')))],
            'message' => ['required', 'string', 'max:2000'],
            'privacy' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('contact-form.validation.attributes');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return __('contact-form.validation.messages');
    }
}
