<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ReleaseChannel;
use App\Enums\ReleaseKind;
use App\Services\Ota\ReleaseCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReleaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request (publish token checked by middleware).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize multipart string inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('mandatory') && is_string($this->input('mandatory'))) {
            $mandatory = filter_var($this->input('mandatory'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $this->merge(['mandatory' => $mandatory ?? $this->input('mandatory')]);
        }

        if (is_string($this->input('native_signature'))) {
            $this->merge(['native_signature' => strtolower(trim($this->input('native_signature'))) ?: null]);
        }

        if (is_string($this->input('kind'))) {
            $this->merge(['kind' => strtolower($this->input('kind'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extension = ReleaseKind::tryFrom((string) $this->input('kind'))?->extension() ?? 'zip,apk';

        return [
            'file' => ['required', 'file', 'max:256000', 'extensions:'.$extension],
            'kind' => ['required', Rule::enum(ReleaseKind::class)],
            'channel' => ['nullable', Rule::enum(ReleaseChannel::class)],
            'fingerprint' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9._-]+$/'],
            'version' => ['required', 'string', 'max:50'],
            'version_code' => ['nullable', 'required_if:kind,apk', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'mandatory' => ['sometimes', 'boolean'],
            'native_signature' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ];
    }

    /**
     * Refuse an OTA bundle when the native code changed since the APK it targets.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(ReleaseCatalog $catalog): array
    {
        return [
            function (Validator $validator) use ($catalog): void {
                if ($this->input('kind') !== ReleaseKind::Ota->value || ! is_string($this->input('fingerprint'))) {
                    return;
                }

                $signature = $this->input('native_signature');
                $apk = $catalog->nativeConflict($this->input('fingerprint'), is_string($signature) ? $signature : null);

                if ($apk !== null) {
                    $validator->errors()->add(
                        'native_signature',
                        "Le code natif a changé depuis l'APK {$apk->version} : publie un nouvel APK plutôt qu'une mise à jour express.",
                    );
                }
            },
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
            'file.extensions' => 'Le fichier doit être un .zip pour une mise à jour express et un .apk pour un APK.',
        ];
    }
}
