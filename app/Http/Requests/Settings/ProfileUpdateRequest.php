<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => [
                'nullable',
                'file',
                'max:2048',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/apng',
            ],
            'remove_avatar' => ['boolean'],
        ];
    }
}
