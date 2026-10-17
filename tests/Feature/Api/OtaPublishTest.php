<?php

use App\Enums\ReleaseChannel;
use App\Enums\ReleaseKind;
use App\Models\AppRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'tarmac.ota.project' => 'tarmac',
        'tarmac.ota.disk' => 'local',
        'tarmac.ota.publish_token' => 'publish-secret',
    ]);

    Storage::fake('local');
});

/**
 * Multipart payload of the publishing endpoint.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function releasePayload(array $overrides = []): array
{
    return [
        'file' => UploadedFile::fake()->createWithContent('bundle.zip', 'zip-bytes'),
        'kind' => 'ota',
        'fingerprint' => 'android-1.0.0b1',
        'version' => '1.0.3',
        'notes' => 'Corrections',
        ...$overrides,
    ];
}

describe('publish token', function () {
    it('rejects requests without a valid token', function () {
        $this->getJson('/api/v1/admin/releases')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Non autorisé.');

        $this->withToken('wrong')->getJson('/api/v1/admin/releases')->assertUnauthorized();
        $this->withToken('wrong')->postJson('/api/v1/admin/releases', releasePayload())->assertUnauthorized();

        expect(AppRelease::query()->count())->toBe(0);
    });

    it('disables publishing when no token is configured', function () {
        config(['tarmac.ota.publish_token' => null]);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload())
            ->assertForbidden()
            ->assertJsonPath('message', 'Publication désactivée.');

        $this->withToken('')->getJson('/api/v1/admin/releases')->assertForbidden();
    });
});

describe('publishing', function () {
    it('publishes an OTA bundle with a server-computed checksum and size', function () {
        $bytes = random_bytes(2048);

        $response = $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload([
                'file' => UploadedFile::fake()->createWithContent('bundle.zip', $bytes),
                'native_signature' => str_repeat('C', 64),
            ]))
            ->assertCreated()
            ->assertJsonPath('release.kind', 'ota')
            ->assertJsonPath('release.channel', 'production')
            ->assertJsonPath('release.project', 'tarmac')
            ->assertJsonPath('release.version', '1.0.3')
            ->assertJsonPath('release.sha256', hash('sha256', $bytes))
            ->assertJsonPath('release.size', 2048)
            ->assertJsonPath('release.native_signature', str_repeat('c', 64))
            ->assertJsonPath('release.is_active', true);

        $release = AppRelease::query()->where('uuid', $response->json('release.uuid'))->firstOrFail();

        expect($release->kind)->toBe(ReleaseKind::Ota)
            ->and($release->path)->toBe("releases/{$release->uuid}.zip")
            ->and($release->version_code)->toBeNull()
            ->and($release->published_at->isToday())->toBeTrue();

        Storage::disk('local')->assertExists($release->path);
        expect(Storage::disk('local')->get($release->path))->toBe($bytes);

        $this->getJson('/api/v1/apps/tarmac/production/android-1.0.0b1?algorithm=1')
            ->assertJsonPath('up_to_date', false)
            ->assertJsonPath('release.uuid', $release->uuid)
            ->assertJsonPath('release.sha256', hash('sha256', $bytes));
    });

    it('publishes an APK on the requested channel', function () {
        $response = $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload([
                'file' => UploadedFile::fake()->createWithContent('tarmac.apk', 'apk-bytes'),
                'kind' => 'apk',
                'channel' => 'staging',
                'fingerprint' => 'android-1.1.0b2',
                'version' => '1.1.0',
                'version_code' => 12,
                'mandatory' => 'true',
                'native_signature' => str_repeat('d', 64),
            ]))
            ->assertCreated()
            ->assertJsonPath('release.kind', 'apk')
            ->assertJsonPath('release.channel', 'staging')
            ->assertJsonPath('release.version_code', 12)
            ->assertJsonPath('release.mandatory', true);

        $release = AppRelease::query()->where('uuid', $response->json('release.uuid'))->firstOrFail();

        expect($release->channel)->toBe(ReleaseChannel::Staging)
            ->and($release->path)->toBe("releases/{$release->uuid}.apk");

        Storage::disk('local')->assertExists($release->path);
    });

    it('validates the upload', function () {
        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload(['file' => null, 'version' => null, 'fingerprint' => 'bad/fingerprint']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file', 'version', 'fingerprint']);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload(['kind' => 'apk', 'file' => UploadedFile::fake()->createWithContent('a.apk', 'x')]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version_code']);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload(['file' => UploadedFile::fake()->createWithContent('a.apk', 'x')]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload(['kind' => 'patch', 'native_signature' => 'xyz']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kind', 'native_signature']);

        expect(AppRelease::query()->count())->toBe(0)
            ->and(Storage::disk('local')->allFiles())->toBe([]);
    });

    it('refuses an OTA bundle whose native code differs from the APK it targets', function () {
        AppRelease::factory()->apk()->create([
            'version' => '1.1.0',
            'fingerprint' => 'android-1.1.0b2',
            'native_signature' => str_repeat('a', 64),
        ]);

        $message = "Le code natif a changé depuis l'APK 1.1.0 : publie un nouvel APK plutôt qu'une mise à jour express.";

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload([
                'fingerprint' => 'android-1.1.0b2',
                'native_signature' => str_repeat('b', 64),
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', $message)
            ->assertJsonValidationErrors(['native_signature' => $message]);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload(['fingerprint' => 'android-1.1.0b2']))
            ->assertUnprocessable()
            ->assertJsonPath('message', $message);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload([
                'fingerprint' => 'android-1.1.0b2',
                'native_signature' => str_repeat('a', 64),
            ]))
            ->assertCreated();

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload([
                'fingerprint' => 'android-1.0.0b1',
                'native_signature' => str_repeat('b', 64),
            ]))
            ->assertCreated();
    });

    it('ignores inactive APKs and APKs without a native signature', function () {
        AppRelease::factory()->apk()->inactive()->create(['fingerprint' => 'android-1.1.0b2', 'native_signature' => str_repeat('a', 64)]);
        AppRelease::factory()->apk()->create(['fingerprint' => 'android-1.1.0b2', 'native_signature' => null]);

        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases', releasePayload([
                'fingerprint' => 'android-1.1.0b2',
                'native_signature' => str_repeat('b', 64),
            ]))
            ->assertCreated();
    });
});

describe('managing releases', function () {
    it('lists releases latest first', function () {
        $old = AppRelease::factory()->create(['published_at' => now()->subDays(2)]);
        $new = AppRelease::factory()->apk()->create(['published_at' => now()]);

        $this->withToken('publish-secret')
            ->getJson('/api/v1/admin/releases')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $new->uuid)
            ->assertJsonPath('data.1.uuid', $old->uuid)
            ->assertJsonPath('meta.per_page', 50);
    });

    it('rolls back by deactivating a release and restores it on activation', function () {
        $previous = AppRelease::factory()->create(['published_at' => now()->subDays(2)]);
        $broken = AppRelease::factory()->create(['published_at' => now()->subDay()]);

        $this->getJson('/api/v1/apps/tarmac/production/android-1.0.0b1?algorithm=1')
            ->assertJsonPath('release.uuid', $broken->uuid);

        $this->withToken('publish-secret')
            ->postJson("/api/v1/admin/releases/{$broken->uuid}/deactivate")
            ->assertOk()
            ->assertJsonPath('release.is_active', false)
            ->assertJsonPath('release.download_url', null);

        expect($broken->fresh()->is_active)->toBeFalse();

        $this->getJson('/api/v1/apps/tarmac/production/android-1.0.0b1?algorithm=1')
            ->assertJsonPath('release.uuid', $previous->uuid);

        $this->withToken('publish-secret')
            ->postJson("/api/v1/admin/releases/{$broken->uuid}/activate")
            ->assertOk()
            ->assertJsonPath('release.is_active', true);

        $this->getJson('/api/v1/apps/tarmac/production/android-1.0.0b1?algorithm=1')
            ->assertJsonPath('release.uuid', $broken->uuid);
    });

    it('returns 404 when toggling an unknown release', function () {
        $this->withToken('publish-secret')
            ->postJson('/api/v1/admin/releases/00000000-0000-0000-0000-000000000000/deactivate')
            ->assertNotFound();
    });
});
