<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DtcLookupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the route code for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper((string) $this->route('code'))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[PCBU][0-9A-F]{4}$/'],
            'make' => ['nullable', 'string', 'max:64'],
            'engine' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Code défaut invalide : il doit ressembler à P0301.',
        ];
    }
}
