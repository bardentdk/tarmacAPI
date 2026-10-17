<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LatestAppBuildRequest extends FormRequest
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
            'version_code' => ['nullable', 'integer', 'min:0'],
            'channel' => ['nullable', 'string', 'max:32'],
        ];
    }
}
