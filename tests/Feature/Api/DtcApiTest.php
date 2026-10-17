<?php

use App\Models\DtcCause;
use App\Models\DtcDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

function fakeClaudeAnswer(): array
{
    return [
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-sonnet-5-5',
        'stop_reason' => 'end_turn',
        'content' => [[
            'type' => 'text',
            'text' => json_encode([
                'title' => 'Capteur de pression du réservoir',
                'system' => 'Antipollution',
                'explanation' => 'Le capteur qui surveille la pression du réservoir donne une valeur bizarre.',
                'urgency' => 'ok',
                'can_drive_note' => 'Tu peux rouler normalement.',
                'causes' => [
                    ['label' => 'Capteur HS', 'probability' => 0.6, 'part' => 'Capteur de pression', 'difficulty' => 'facile', 'cost_min' => 60, 'cost_max' => 180],
                    ['label' => 'Faisceau abîmé', 'probability' => 0.4, 'part' => 'Faisceau', 'difficulty' => 'moyenne', 'cost_min' => 50, 'cost_max' => 200],
                ],
            ]),
        ]],
    ];
}

it('explains a code from the knowledge base', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dtc/p0301')
        ->assertOk()
        ->assertJsonPath('code', 'P0301')
        ->assertJsonPath('source', 'kb')
        ->assertJsonPath('urgency', 'stop')
        ->assertJsonPath('generic', false)
        ->assertJsonStructure(['code', 'title', 'system', 'explanation', 'urgency', 'causes' => [['label', 'probability', 'part', 'cost_min', 'cost_max']], 'source']);
});

it('prefers a make specific definition', function () {
    Sanctum::actingAs(User::factory()->create());
    $definition = DtcDefinition::factory()->forVehicle('Volkswagen')->create(['code' => 'P0299', 'title' => 'Underboost typique VW']);
    DtcCause::factory()->for($definition, 'definition')->create();

    $this->getJson('/api/v1/dtc/P0299?make=Volkswagen&engine=EA888')
        ->assertOk()
        ->assertJsonPath('title', 'Underboost typique VW')
        ->assertJsonCount(1, 'causes');

    $this->getJson('/api/v1/dtc/P0299?make=Renault')
        ->assertOk()
        ->assertJsonPath('title', fn (string $title) => $title !== 'Underboost typique VW');
});

it('returns a structural explanation when the code is unknown and no AI key is set', function () {
    config(['tarmac.anthropic.api_key' => null]);
    Http::fake();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dtc/U0123')
        ->assertOk()
        ->assertJsonPath('code', 'U0123')
        ->assertJsonPath('system', 'Réseau de communication')
        ->assertJsonPath('generic', true)
        ->assertJsonPath('causes', []);

    Http::assertNothingSent();
});

it('asks Claude for unknown codes and caches the answer', function () {
    config(['tarmac.anthropic.api_key' => 'test-key', 'tarmac.anthropic.model' => 'claude-sonnet-5-5']);
    Http::fake(['api.anthropic.com/*' => Http::response(fakeClaudeAnswer())]);
    Sanctum::actingAs(User::factory()->onTrial()->create());

    $this->getJson('/api/v1/dtc/P0452?make=Peugeot&engine=EP6')
        ->assertOk()
        ->assertJsonPath('source', 'ai')
        ->assertJsonPath('title', 'Capteur de pression du réservoir')
        ->assertJsonCount(2, 'causes');

    $this->getJson('/api/v1/dtc/P0452?make=Peugeot&engine=EP6')
        ->assertOk()
        ->assertJsonPath('source', 'ai');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'test-key')
        && $request->hasHeader('anthropic-version', '2023-06-01')
        && $request['model'] === 'claude-sonnet-5-5'
        && $request['output_config']['format']['type'] === 'json_schema');

    expect(DtcDefinition::where(['code' => 'P0452', 'make' => 'peugeot', 'engine' => 'ep6', 'source' => 'ai'])->exists())->toBeTrue();
});

it('falls back to the structural explanation when Claude fails', function () {
    config(['tarmac.anthropic.api_key' => 'test-key']);
    Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['type' => 'overloaded_error']], 529)]);
    Sanctum::actingAs(User::factory()->onTrial()->create());

    $this->getJson('/api/v1/dtc/P0452')
        ->assertOk()
        ->assertJsonPath('generic', true);

    expect(DtcDefinition::where('code', 'P0452')->exists())->toBeFalse();
});

it('does not call Claude for free plan users', function () {
    config(['tarmac.anthropic.api_key' => 'test-key']);
    Http::fake();
    Sanctum::actingAs(User::factory()->free()->create());

    $this->getJson('/api/v1/dtc/P0452')->assertOk()->assertJsonPath('generic', true);

    Http::assertNothingSent();
});

it('validates the code format', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dtc/XYZ')->assertUnprocessable()->assertJsonValidationErrors('code');
});
