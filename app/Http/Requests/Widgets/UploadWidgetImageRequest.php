<?php

namespace App\Http\Requests\Widgets;

use Illuminate\Foundation\Http\FormRequest;

class UploadWidgetImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/apng',
                'max:5120',
            ],
        ];
    }
}
