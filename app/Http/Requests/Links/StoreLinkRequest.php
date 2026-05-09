<?php

namespace App\Http\Requests\Links;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9._~-]+$/',
                Rule::notIn([
                    'admin',
                    'confirm-password',
                    'dashboard',
                    'email-verification',
                    'forgot-password',
                    'login',
                    'logout',
                    'register',
                    'reset-password',
                    'settings',
                    'two-factor-challenge',
                    'verify-email',
                ]),
                Rule::unique('links', 'slug'),
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'title_id' => [
                'nullable',
                'integer',
                Rule::exists('titles', 'id')->where('is_active', true),
            ],
            'bio' => ['nullable', 'string', 'max:280'],
        ];
    }
}
