<?php

namespace App\Http\Requests\Messages;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
            'sender_mode' => ['required', 'in:anonymous,named'],
            'sender_display_name' => ['nullable', 'string', 'max:50'],

            'has_gift' => ['required', 'boolean'],
            'gift_amount' => ['nullable', 'integer', 'min:500', 'max:50000'],
            'gift_label' => ['nullable', 'string', 'max:50'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
