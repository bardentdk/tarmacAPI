<?php

use App\Models\AppRelease;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    config([
        'tarmac.ota.project' => 'tarmac',
        'tarmac.ota.disk' => 'local',
        'tarmac.ota.download_ttl_minutes' => 30,
    ]);

    Storage::fake('local');
});

/**
 * OTA check URL exactly as built by the nativephp/mobile-ota plugin.
 */
function otaCheckUrl(string $fingerprint = 'android-1.0.0b1', ?string $held = null, string $arc = 'production', string $project = 'tarmac', ?string $shellBuiltAt = null): string
{
    $url = "/api/v1/apps/{$project}/{$arc}/{$fingerprint}".($held !== null ? "/{$held}" : '').'?algorithm=1';

    return $shellBuiltAt !== null ? $url.'&shell_built_at='.urlencode($shellBuiltAt) : $url;
}

describe('OTA check', function () {
    it('is up to date when nothing was published', function () {
        $this->getJson(otaCheckUrl())
            ->assertOk()
            ->assertExactJson(['up_to_date' => true, 'release' => null, 'download_url' => null]);
    });

    it('offers the latest active bundle for the shell fingerprint', function () {
        AppRelease::factory()->create(['published_at' => now()->subDays(3)]);
        $latest = AppRelease::factory()->create([
            'version' => '1.0.3',
            'notes' => 'Corrections',
            'sha256' => str_repeat('a', 64),
            'size' => 12345,
            'published_at' => now()->subDay(),
        ]);
        AppRelease::factory()->inactive()->create(['published_at' => now()]);
        AppRelease::factory()->staging()->create(['published_at' => now()]);
        AppRelease::factory()->create(['fingerprint' => 'android-1.1.0b1', 'published_at' => now()]);
        AppRelease::factory()->apk()->create(['published_at' => now()]);
        AppRelease::factory()->create(['project' => 'other', 'published_at' => now()]);

        $response = $this->getJson(otaCheckUrl())
            ->assertOk()
            ->assertJsonPath('up_to_date', false)
            ->assertJsonPath('release', [
                'uuid' => $latest->uuid,
                'version' => '1.0.3',
                'notes' => 'Corrections',
                'sha256' => str_repeat('a', 64),
                'size' => 12345,
                'commit' => null,
                'published_at' => $latest->published_at->utc()->toIso8601ZuluString(),
            ]);

        expect($response->json('release.published_at'))->toEndWith('Z')
            ->and($response->json('download_url'))
            ->toContain("/api/v1/releases/{$latest->uuid}/download?expires=")
            ->toContain('signature=');
    });

    it('serves download links over HTTPS when the app URL is HTTPS', function () {
        AppRelease::factory()->create();

        config(['app.url' => 'https://api.tarmac.example']);
        expect($this->getJson('http://api.tarmac.example'.otaCheckUrl())->json('download_url'))
            ->toStartWith('https://api.tarmac.example/api/v1/releases/');

        config(['app.url' => 'http://localhost']);
        expect($this->getJson('http://localhost'.otaCheckUrl())->json('download_url'))
            ->toStartWith('http://localhost/api/v1/releases/');
    });

    it('accepts fingerprints containing dots', function () {
        $release = AppRelease::factory()->create(['fingerprint' => 'android-2.10.0.rc.1']);

        $this->getJson(otaCheckUrl('android-2.10.0.rc.1'))
            ->assertOk()
            ->assertJsonPath('release.uuid', $release->uuid);
    });

    it('is up to date when the device holds the latest release', function () {
        $release = AppRelease::factory()->create();

        $this->getJson(otaCheckUrl(held: $release->uuid))
            ->assertOk()
            ->assertExactJson(['up_to_date' => true, 'release' => null, 'download_url' => null]);

        $this->getJson(otaCheckUrl(held: strtoupper($release->uuid)))->assertJsonPath('up_to_date', true);
    });

    it('offers a newer release to a device holding an older one', function () {
        $held = AppRelease::factory()->create(['published_at' => now()->subDays(2)]);
        $latest = AppRelease::factory()->create(['published_at' => now()->subDay()]);

        $this->getJson(otaCheckUrl(held: $held->uuid))
            ->assertOk()
            ->assertJsonPath('up_to_date', false)
            ->assertJsonPath('release.uuid', $latest->uuid);
    });

    it('keeps a device on a held release newer than the latest active one', function () {
        $previous = AppRelease::factory()->create(['published_at' => now()->subDays(2)]);
        $rolledBack = AppRelease::factory()->inactive()->create(['published_at' => now()->subDay()]);

        $this->getJson(otaCheckUrl(held: $rolledBack->uuid))->assertJsonPath('up_to_date', true);
        $this->getJson(otaCheckUrl())->assertJsonPath('release.uuid', $previous->uuid);
    });

    it('offers the latest release when the held release is unknown', function () {
        $latest = AppRelease::factory()->create();

        $this->getJson(otaCheckUrl(held: (string) Str::uuid()))
            ->assertJsonPath('up_to_date', false)
            ->assertJsonPath('release.uuid', $latest->uuid);
    });

    it('treats releases published before the shell was built as already shipped', function () {
        $release = AppRelease::factory()->create(['published_at' => now()->subDay()]);

        $this->getJson(otaCheckUrl(shellBuiltAt: now()->toIso8601ZuluString()))
            ->assertExactJson(['up_to_date' => true, 'release' => null, 'download_url' => null]);

        $this->getJson(otaCheckUrl(shellBuiltAt: $release->published_at->toIso8601ZuluString()))
            ->assertJsonPath('up_to_date', true);

        $this->getJson(otaCheckUrl(shellBuiltAt: now()->subDays(2)->toIso8601ZuluString()))
            ->assertJsonPath('up_to_date', false)
            ->assertJsonPath('release.uuid', $release->uuid);
    });

    it('returns 404 for an unknown project', function () {
        AppRelease::factory()->create();

        $this->getJson(otaCheckUrl(project: 'unknown'))
            ->assertNotFound()
            ->assertJsonPath('message', 'Projet inconnu.');
    });

    it('serves the staging arc and falls back to production for unknown arcs', function () {
        $production = AppRelease::factory()->create();
        $staging = AppRelease::factory()->staging()->create();

        $this->getJson(otaCheckUrl(arc: 'staging'))->assertJsonPath('release.uuid', $staging->uuid);
        $this->getJson(otaCheckUrl(arc: 'beta'))->assertJsonPath('release.uuid', $production->uuid);
        $this->getJson(otaCheckUrl(arc: 'production'))->assertJsonPath('release.uuid', $production->uuid);
    });
});

describe('APK check', function () {
    it('reports nothing available without a newer APK', function () {
        AppRelease::factory()->apk()->create(['version_code' => 5]);
        AppRelease::factory()->create();

        $this->getJson('/api/v1/app-builds/tarmac/android/latest?version_code=5')
            ->assertOk()
            ->assertExactJson(['available' => false, 'release' => null, 'download_url' => null]);
    });

    it('offers the highest active version code above the installed one', function () {
        AppRelease::factory()->apk()->create(['version_code' => 4]);
        $latest = AppRelease::factory()->apk()->create([
            'version' => '1.2.0',
            'version_code' => 7,
            'notes' => 'Nouveau shell',
            'sha256' => str_repeat('b', 64),
            'size' => 4_200_000,
            'published_at' => now()->subDay(),
        ]);
        AppRelease::factory()->apk()->create(['version_code' => 6, 'published_at' => now()]);
        AppRelease::factory()->apk()->inactive()->create(['version_code' => 9]);
        AppRelease::factory()->apk()->staging()->create(['version_code' => 8]);

        $response = $this->getJson('/api/v1/app-builds/tarmac/android/latest?version_code=5')
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('release', [
                'uuid' => $latest->uuid,
                'version' => '1.2.0',
                'version_code' => 7,
                'notes' => 'Nouveau shell',
                'sha256' => str_repeat('b', 64),
                'size' => 4_200_000,
                'mandatory' => false,
                'published_at' => $latest->published_at->utc()->toIso8601ZuluString(),
            ]);

        expect($response->json('download_url'))->toContain("/api/v1/releases/{$latest->uuid}/download?expires=");
    });

    it('filters by channel', function () {
        AppRelease::factory()->apk()->create(['version_code' => 2]);
        $staging = AppRelease::factory()->apk()->staging()->create(['version_code' => 3]);

        $this->getJson('/api/v1/app-builds/tarmac/android/latest?version_code=1&channel=staging')
            ->assertJsonPath('release.uuid', $staging->uuid);
    });

    it('marks the update mandatory when a skipped build is mandatory', function () {
        AppRelease::factory()->apk()->create(['version_code' => 2, 'mandatory' => true]);
        AppRelease::factory()->apk()->create(['version_code' => 3, 'mandatory' => false]);

        $this->getJson('/api/v1/app-builds/tarmac/android/latest?version_code=1')
            ->assertJsonPath('release.version_code', 3)
            ->assertJsonPath('release.mandatory', true);

        $this->getJson('/api/v1/app-builds/tarmac/android/latest?version_code=2')
            ->assertJsonPath('release.mandatory', false);
    });

    it('returns 404 for an unknown project', function () {
        $this->getJson('/api/v1/app-builds/unknown/android/latest?version_code=1')->assertNotFound();
    });
});

describe('download', function () {
    it('streams the exact bytes of a release through a signed URL', function () {
        $bytes = random_bytes(4096);
        $release = AppRelease::factory()->create(['version' => '1.0.3', 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes)]);
        Storage::disk('local')->put($release->path, $bytes);

        $url = $this->getJson(otaCheckUrl())->json('download_url');

        $response = $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip')
            ->assertHeader('Content-Length', (string) strlen($bytes))
            ->assertDownload('tarmac-1.0.3.zip');

        expect($response->streamedContent())->toBe($bytes)
            ->and(hash('sha256', $response->streamedContent()))->toBe($release->sha256);
    });

    it('serves APKs with the Android package content type', function () {
        $release = AppRelease::factory()->apk()->create(['version' => '1.2.0']);
        Storage::disk('local')->put($release->path, 'apk-bytes');

        $url = $this->getJson('/api/v1/app-builds/tarmac/android/latest?version_code=0')->json('download_url');

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertDownload('tarmac-1.2.0.apk');
    });

    it('rejects unsigned and tampered URLs', function () {
        $release = AppRelease::factory()->create();
        Storage::disk('local')->put($release->path, 'zip');

        $this->get("/api/v1/releases/{$release->uuid}/download")->assertForbidden();

        $url = $this->getJson(otaCheckUrl())->json('download_url');
        $this->get(str_replace('signature=', 'signature=0', $url))->assertForbidden();
    });

    it('rejects expired URLs', function () {
        $release = AppRelease::factory()->create();
        Storage::disk('local')->put($release->path, 'zip');

        $url = $this->getJson(otaCheckUrl())->json('download_url');

        $this->travel(31)->minutes();

        $this->get($url)->assertForbidden();
    });

    it('returns 404 for a deactivated release', function () {
        $release = AppRelease::factory()->create();
        Storage::disk('local')->put($release->path, 'zip');

        $url = $this->getJson(otaCheckUrl())->json('download_url');
        $release->update(['is_active' => false]);

        $this->get($url)->assertNotFound();
    });

    it('redirects to a temporary URL on object storage disks', function () {
        config([
            'filesystems.disks.releases' => ['driver' => 's3', 'bucket' => 'releases', 'region' => 'auto'],
            'tarmac.ota.disk' => 'releases',
        ]);
        Storage::fake('releases');

        $release = AppRelease::factory()->create();
        Storage::disk('releases')->put($release->path, 'zip');

        $url = $this->getJson(otaCheckUrl())->json('download_url');

        $response = $this->get($url)->assertRedirect();

        expect($response->headers->get('Location'))->toContain("releases/{$release->uuid}.zip?expiration=");
    });
});
