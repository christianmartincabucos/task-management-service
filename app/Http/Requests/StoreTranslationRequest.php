<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:10'],
            'content' => ['required', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'The translation key is required.',
            'locale.required' => 'The locale is required.',
            'content.required' => 'The translation content is required.',
        ];
    }
}
