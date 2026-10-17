<?php

namespace App\Http\Requests;

use App\Enums\Drivetrain;
use App\Enums\Fuel;
use App\Enums\Gearbox;
use App\Enums\TuningStage;
use App\Models\User;
use App\Services\Entitlements;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVehicleRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'make' => ['required', 'string', 'max:64'],
            'model' => ['required', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'engine' => ['nullable', 'string', 'max:64'],
            'fuel' => ['required', Rule::enum(Fuel::class)],
            'gearbox' => ['required', Rule::enum(Gearbox::class)],
            'drivetrain' => ['required', Rule::enum(Drivetrain::class)],
            'mass_kg' => ['required', 'integer', 'between:300,5000'],
            'tire_size' => ['nullable', 'string', 'max:32'],
            'tuning_stage' => ['required', Rule::enum(TuningStage::class)],
            'wheel_radius_m' => ['nullable', 'numeric', 'between:0.2,0.6'],
            'cda' => ['nullable', 'numeric', 'between:0.2,2'],
            'redline_rpm' => ['nullable', 'integer', 'between:2000,12000'],
            'shift_light_start_rpm' => ['nullable', 'integer', 'between:1500,12000'],
            'vin' => ['nullable', 'string', 'size:17'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $user */
                $user = $this->user();
                $maxVehicles = app(Entitlements::class)->features($user)['max_vehicles'];

                if ($maxVehicles !== null && $user->vehicles()->count() >= $maxVehicles) {
                    $validator->errors()->add('name', "Ton offre permet {$maxVehicles} véhicule(s). Passe à l'offre supérieure pour en ajouter.");
                }
            },
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'make' => 'marque',
            'model' => 'modèle',
            'year' => 'année',
            'engine' => 'moteur',
            'fuel' => 'carburant',
            'gearbox' => 'boîte',
            'drivetrain' => 'transmission',
            'mass_kg' => 'masse',
            'tire_size' => 'pneus',
            'tuning_stage' => 'niveau de préparation',
        ];
    }
}
