<?php

namespace App\Http\Requests\Api;

use App\Services\Sync\SyncService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the envelope only: each record is validated individually by the
 * SyncService so one bad record does not reject the whole batch.
 */
class SyncRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $max = (int) config('tarmac.sync.max_records_per_type');
        $rules = [];

        foreach (SyncService::TYPES as $type) {
            $rules[$type] = ['sometimes', 'nullable', 'array', "max:{$max}"];
            $rules["{$type}.*"] = ['array'];
        }

        return $rules;
    }
}
