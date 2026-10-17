<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OtaCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request (public endpoint).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'algorithm' => ['nullable', 'string', 'max:16'],
            'shell_built_at' => ['nullable', 'date'],
        ];
    }
}
