<?php

namespace App\Http\Requests\Widgets;

use Illuminate\Foundation\Http\FormRequest;

class SyncWidgetsRequest extends FormRequest
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
            'widgets' => ['present', 'array', 'max:50'],
            'widgets.*.id' => ['nullable'],
            'widgets.*.type' => ['required', 'string'],
            'widgets.*.content' => ['nullable', 'string', 'max:2000'],
            'widgets.*.thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'widgets.*.x' => ['required', 'integer'],
            'widgets.*.y' => ['required', 'integer'],
            'widgets.*.w' => ['required', 'integer'],
            'widgets.*.h' => ['required', 'integer'],
            'widgets.*.x_mobile' => ['required', 'integer'],
            'widgets.*.y_mobile' => ['required', 'integer'],
            'widgets.*.w_mobile' => ['required', 'integer'],
            'widgets.*.h_mobile' => ['required', 'integer'],
            'widgets.*.settings' => ['nullable', 'array'],
            'widgets.*.settings.title' => ['nullable', 'string', 'max:4500'],
            'widgets.*.settings.url' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
