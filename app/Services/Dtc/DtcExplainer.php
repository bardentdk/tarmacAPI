<?php

namespace App\Services\Dtc;

use App\Enums\DtcUrgency;
use App\Models\DtcCause;
use App\Models\DtcDefinition;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Explains an OBD-II trouble code in plain French.
 *
 * Lookup order: knowledge base (make/engine specific, then generic), cached
 * AI answers, a fresh AI answer (when a key is configured and allowed), then
 * a structural explanation decoded from the code itself.
 */
class DtcExplainer
{
    /**
     * @return array{code: string, title: string, system: string, explanation: string, urgency: string, can_drive_note: string|null, causes: list<array{label: string, probability: float, part: string|null, difficulty: string|null, cost_min: int|null, cost_max: int|null}>, source: string, generic: bool}
     */
    public function explain(string $code, ?string $make = null, ?string $engine = null, bool $allowAi = true): array
    {
        $code = strtoupper(trim($code));
        $make = $this->normalizeKey($make);
        $engine = $this->normalizeKey($engine);

        $definition = $this->findDefinition($code, $make, $engine);

        if ($definition === null && $allowAi && filled(config('tarmac.anthropic.api_key'))) {
            $definition = $this->askClaude($code, $make, $engine);
        }

        if ($definition === null) {
            return $this->genericExplanation($code);
        }

        return $this->present($definition);
    }

    /**
     * Find the most specific stored definition.
     */
    public function findDefinition(string $code, string $make = '', string $engine = ''): ?DtcDefinition
    {
        $candidates = DtcDefinition::query()
            ->with('causes')
            ->where('code', $code)
            ->whereIn('make', array_unique([$make, '']))
            ->whereIn('engine', array_unique([$engine, '']))
            ->get();

        return $candidates
            ->sortByDesc(fn (DtcDefinition $definition): int => ($definition->make !== '' ? 2 : 0) + ($definition->engine !== '' ? 1 : 0))
            ->first();
    }

    /**
     * Normalize a make / engine key ('' = generic).
     */
    public function normalizeKey(?string $value): string
    {
        return Str::limit(Str::lower(trim((string) $value)), 64, '');
    }

    /**
     * @return array{code: string, title: string, system: string, explanation: string, urgency: string, can_drive_note: string|null, causes: list<array{label: string, probability: float, part: string|null, difficulty: string|null, cost_min: int|null, cost_max: int|null}>, source: string, generic: bool}
     */
    public function present(DtcDefinition $definition): array
    {
        return [
            'code' => $definition->code,
            'title' => $definition->title,
            'system' => $definition->system,
            'explanation' => $definition->explanation,
            'urgency' => $definition->urgency,
            'can_drive_note' => $definition->can_drive_note,
            'causes' => array_values($definition->causes->map(fn (DtcCause $cause): array => [
                'label' => $cause->label,
                'probability' => $cause->probability,
                'part' => $cause->part,
                'difficulty' => $cause->difficulty,
                'cost_min' => $cause->cost_min,
                'cost_max' => $cause->cost_max,
            ])->all()),
            'source' => $definition->source,
            'generic' => false,
        ];
    }

    /**
     * Ask Claude for an explanation and cache it as an 'ai' definition.
     */
    private function askClaude(string $code, string $make, string $engine): ?DtcDefinition
    {
        $vehicle = trim(($make !== '' ? Str::title($make) : 'voiture').($engine !== '' ? ' moteur '.$engine : ''));

        $prompt = <<<TXT
        Tu es un mécanicien automobile qui explique les codes défauts OBD-II à un passionné, en français, en tutoyant, avec des phrases courtes et sans jargon non expliqué. Pas d'emoji.

        Explique le code {$code} pour une {$vehicle}.
        - title : intitulé court du défaut (max 80 caractères).
        - system : système concerné (ex. « Allumage », « Admission », « Antipollution »).
        - explanation : explication imagée en 2 à 4 phrases.
        - urgency : « ok » (tu peux rouler), « caution » (roule prudemment, fais vérifier) ou « stop » (arrête-toi).
        - can_drive_note : une phrase de conseil sur la conduite en attendant la réparation.
        - causes : 3 à 5 causes probables classées de la plus à la moins probable, avec probability entre 0 et 1, la pièce concernée, la difficulté (« facile », « moyenne » ou « difficile ») et une fourchette de coût de réparation en euros (main-d'œuvre comprise, prix France).
        TXT;

        try {
            $request = Http::withHeaders([
                'x-api-key' => (string) config('tarmac.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
            ])
                ->acceptJson()
                ->timeout((int) config('tarmac.anthropic.timeout'));

            $body = [
                'model' => (string) config('tarmac.anthropic.model'),
                'max_tokens' => 8000,
                'output_config' => [
                    'effort' => (string) config('tarmac.anthropic.effort'),
                    'format' => ['type' => 'json_schema', 'schema' => $this->responseSchema()],
                ],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ];

            if (config('tarmac.anthropic.fallbacks')) {
                $request = $request->withHeaders(['anthropic-beta' => 'server-side-fallback-2026-07-01']);
                $body['fallbacks'] = 'default';
            }

            $response = $request->post((string) config('tarmac.anthropic.url'), $body);

            if ($response->failed() || $response->json('stop_reason') === 'refusal') {
                Log::warning('DTC explanation request failed', ['code' => $code, 'status' => $response->status()]);

                return null;
            }

            $text = '';

            foreach ((array) $response->json('content', []) as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'text') {
                    $text .= (string) ($block['text'] ?? '');
                }
            }

            $data = json_decode($text, true);

            if (! is_array($data) || ! $this->isValidAnswer($data)) {
                Log::warning('DTC explanation could not be parsed', ['code' => $code]);

                return null;
            }

            return $this->storeAiAnswer($code, $make, $engine, $data);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeAiAnswer(string $code, string $make, string $engine, array $data): DtcDefinition
    {
        try {
            return DB::transaction(function () use ($code, $make, $engine, $data): DtcDefinition {
                $definition = DtcDefinition::create([
                    'code' => $code,
                    'make' => $make,
                    'engine' => $engine,
                    'title' => Str::limit((string) $data['title'], 250),
                    'system' => Str::limit((string) $data['system'], 60),
                    'explanation' => (string) $data['explanation'],
                    'urgency' => (string) $data['urgency'],
                    'can_drive_note' => isset($data['can_drive_note']) ? (string) $data['can_drive_note'] : null,
                    'source' => 'ai',
                ]);

                foreach (array_values($data['causes']) as $position => $cause) {
                    $definition->causes()->create([
                        'label' => Str::limit((string) ($cause['label'] ?? ''), 250),
                        'probability' => max(0, min(1, (float) ($cause['probability'] ?? 0))),
                        'part' => isset($cause['part']) ? Str::limit((string) $cause['part'], 250) : null,
                        'difficulty' => isset($cause['difficulty']) ? Str::limit((string) $cause['difficulty'], 16, '') : null,
                        'cost_min' => isset($cause['cost_min']) ? max(0, (int) $cause['cost_min']) : null,
                        'cost_max' => isset($cause['cost_max']) ? max(0, (int) $cause['cost_max']) : null,
                        'position' => $position,
                    ]);
                }

                return $definition->load('causes');
            });
        } catch (UniqueConstraintViolationException) {
            /** @var DtcDefinition */
            return DtcDefinition::with('causes')
                ->where(['code' => $code, 'make' => $make, 'engine' => $engine])
                ->firstOrFail();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isValidAnswer(array $data): bool
    {
        return is_string($data['title'] ?? null)
            && is_string($data['system'] ?? null)
            && is_string($data['explanation'] ?? null)
            && in_array($data['urgency'] ?? null, DtcUrgency::values(), true)
            && is_array($data['causes'] ?? null);
    }

    /**
     * JSON schema for structured outputs.
     *
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'system', 'explanation', 'urgency', 'can_drive_note', 'causes'],
            'properties' => [
                'title' => ['type' => 'string'],
                'system' => ['type' => 'string'],
                'explanation' => ['type' => 'string'],
                'urgency' => ['type' => 'string', 'enum' => DtcUrgency::values()],
                'can_drive_note' => ['type' => 'string'],
                'causes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['label', 'probability', 'part', 'difficulty', 'cost_min', 'cost_max'],
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'probability' => ['type' => 'number'],
                            'part' => ['type' => 'string'],
                            'difficulty' => ['type' => 'string', 'enum' => ['facile', 'moyenne', 'difficile']],
                            'cost_min' => ['type' => 'integer'],
                            'cost_max' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Structural explanation decoded from the code (P/C/B/U + digits).
     *
     * @return array{code: string, title: string, system: string, explanation: string, urgency: string, can_drive_note: string|null, causes: list<array{label: string, probability: float, part: string|null, difficulty: string|null, cost_min: int|null, cost_max: int|null}>, source: string, generic: bool}
     */
    public function genericExplanation(string $code): array
    {
        $family = $code[0] ?? 'P';
        $standard = $code[1] ?? '0';
        $subsystem = $code[2] ?? '0';

        $families = [
            'P' => ['Groupe motopropulseur', 'le moteur, l\'injection, l\'allumage, l\'antipollution ou la boîte'],
            'C' => ['Châssis', 'le freinage (ABS), la direction ou les suspensions'],
            'B' => ['Carrosserie', 'l\'habitacle : airbags, climatisation, sièges, éclairage'],
            'U' => ['Réseau de communication', 'les échanges entre calculateurs sur le bus CAN'],
        ];

        $powertrainSubsystems = [
            '0' => 'Dosage air / carburant et antipollution auxiliaire',
            '1' => 'Dosage air / carburant',
            '2' => 'Circuit d\'injection',
            '3' => 'Allumage ou ratés de combustion',
            '4' => 'Antipollution auxiliaire',
            '5' => 'Régulation de vitesse, ralenti et entrées auxiliaires',
            '6' => 'Calculateur et sorties',
            '7' => 'Transmission',
            '8' => 'Transmission',
            '9' => 'Transmission',
            'A' => 'Propulsion hybride',
            'B' => 'Propulsion hybride',
            'C' => 'Propulsion hybride',
        ];

        [$familyLabel, $familyScope] = $families[$family] ?? $families['P'];
        $system = $family === 'P' ? ($powertrainSubsystems[$subsystem] ?? $familyLabel) : $familyLabel;
        $origin = match ($standard) {
            '0', '2' => 'C\'est un code normalisé : il a le même sens sur toutes les voitures.',
            '1' => 'C\'est un code propre au constructeur : son sens exact dépend de ta marque.',
            default => 'C\'est un code en partie normalisé, en partie propre au constructeur.',
        };

        $urgency = DtcUrgency::Caution;

        return [
            'code' => $code,
            'title' => $familyLabel.' — '.$system,
            'system' => $system,
            'explanation' => "Ce code concerne {$familyScope}. {$origin} On n'a pas encore d'explication détaillée pour ce code : fais lire les données figées (freeze frame) et surveille si le voyant revient.",
            'urgency' => $urgency->value,
            'can_drive_note' => 'Roule tranquille et sans tirer dans les tours tant que le défaut n\'est pas identifié.',
            'causes' => [],
            'source' => 'kb',
            'generic' => true,
        ];
    }
}
