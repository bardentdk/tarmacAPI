<?php

namespace App\Services\Ota;

use App\Enums\ReleaseChannel;
use App\Enums\ReleaseKind;
use App\Models\AppRelease;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

/**
 * Answers "what should this device install?" for OTA bundles and native APKs.
 */
class ReleaseCatalog
{
    /**
     * The project id the mobile app is published under.
     */
    public function project(): string
    {
        return (string) config('tarmac.ota.project');
    }

    /**
     * Whether the given project id is the one served by this API.
     */
    public function knowsProject(string $project): bool
    {
        return $project !== '' && $project === $this->project();
    }

    /**
     * Latest active OTA bundle built against the given native shell.
     */
    public function latestOta(ReleaseChannel $channel, string $fingerprint): ?AppRelease
    {
        return AppRelease::query()
            ->where('project', $this->project())
            ->ofKind(ReleaseKind::Ota)
            ->where('channel', $channel->value)
            ->where('fingerprint', $fingerprint)
            ->active()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Whether a device holding $heldUuid (null = code shipped in the APK) already has
     * everything $latest brings.
     *
     * A held release that is newer than $latest (e.g. after $latest's successor was
     * deactivated) also counts as up to date: devices never roll back on their own.
     */
    public function isUpToDate(?AppRelease $latest, ?string $heldUuid, ?DateTimeInterface $shellBuiltAt): bool
    {
        if ($latest === null) {
            return true;
        }

        if ($heldUuid !== null && $heldUuid !== '') {
            $heldUuid = strtolower($heldUuid);

            if ($heldUuid === $latest->uuid) {
                return true;
            }

            $held = AppRelease::query()
                ->where('project', $this->project())
                ->ofKind(ReleaseKind::Ota)
                ->where('uuid', $heldUuid)
                ->first();

            if ($held !== null && $latest->published_at->lessThanOrEqualTo($held->published_at)) {
                return true;
            }
        }

        return $shellBuiltAt !== null && $latest->published_at->lessThanOrEqualTo($shellBuiltAt);
    }

    /**
     * Latest active APK newer than the installed version code.
     */
    public function latestApk(ReleaseChannel $channel, int $installedVersionCode): ?AppRelease
    {
        return $this->newerApks($channel, $installedVersionCode)
            ->orderByDesc('version_code')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Whether any active APK between the installed one and $latest is mandatory, so
     * skipping a mandatory build still forces the update.
     */
    public function apkUpdateIsMandatory(AppRelease $latest, int $installedVersionCode): bool
    {
        return $latest->mandatory || $this->newerApks($latest->channel, $installedVersionCode)
            ->where('version_code', '<=', (int) $latest->version_code)
            ->where('mandatory', true)
            ->exists();
    }

    /**
     * Active APK whose native code differs from what an OTA bundle claims to target.
     */
    public function nativeConflict(string $fingerprint, ?string $nativeSignature): ?AppRelease
    {
        return AppRelease::query()
            ->where('project', $this->project())
            ->ofKind(ReleaseKind::Apk)
            ->where('fingerprint', $fingerprint)
            ->whereNotNull('native_signature')
            ->when(
                $nativeSignature !== null,
                fn ($query) => $query->where('native_signature', '!=', strtolower((string) $nativeSignature)),
            )
            ->active()
            ->orderByDesc('published_at')
            ->first();
    }

    /**
     * Short-lived signed URL to download a release (no auth header needed).
     *
     * The signature covers the path and query only ("signed:relative"), so it survives
     * the edge proxy rewriting the scheme or host; the link is served over HTTPS
     * whenever APP_URL is HTTPS (Android refuses cleartext downloads).
     */
    public function downloadUrl(AppRelease $release): string
    {
        $relativeUrl = URL::temporarySignedRoute(
            'api.v1.releases.download',
            now()->addMinutes($this->downloadTtlMinutes()),
            ['uuid' => $release->uuid],
            absolute: false,
        );

        $request = request();
        $root = str_starts_with((string) config('app.url'), 'https://')
            ? 'https://'.$request->getHttpHost()
            : $request->getSchemeAndHttpHost();

        return $root.$relativeUrl;
    }

    /**
     * Lifetime of download links, in minutes.
     */
    public function downloadTtlMinutes(): int
    {
        return max(1, (int) config('tarmac.ota.download_ttl_minutes', 30));
    }

    /**
     * @return Builder<AppRelease>
     */
    private function newerApks(ReleaseChannel $channel, int $installedVersionCode): Builder
    {
        return AppRelease::query()
            ->where('project', $this->project())
            ->where('platform', 'android')
            ->ofKind(ReleaseKind::Apk)
            ->where('channel', $channel->value)
            ->where('version_code', '>', $installedVersionCode)
            ->active();
    }
}
