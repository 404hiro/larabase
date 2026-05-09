<?php

namespace App\Http\Requests\Links;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('link')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:280'],
            'avatar' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/apng',
                'max:2048',
            ],
            'is_published' => ['nullable', 'boolean'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
