<?php

namespace App\Services\Ota;

use App\Enums\ReleaseChannel;
use App\Enums\ReleaseKind;
use App\Models\AppRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Stores an uploaded build on the release disk and records it.
 */
class ReleasePublisher
{
    public function __construct(private ReleaseCatalog $catalog) {}

    /**
     * Publish a release; checksum and size are computed from the uploaded bytes.
     *
     * @param  array{kind: string, channel?: string|null, fingerprint: string, version: string, version_code?: int|string|null, notes?: string|null, mandatory?: bool|null, native_signature?: string|null}  $attributes
     */
    public function publish(UploadedFile $file, array $attributes): AppRelease
    {
        $kind = ReleaseKind::from($attributes['kind']);
        $uuid = (string) Str::uuid();
        $sha256 = hash_file('sha256', (string) $file->getRealPath());
        $size = (int) $file->getSize();

        $disk = Storage::disk((string) config('tarmac.ota.disk'));
        $path = $disk->putFileAs('releases', $file, "{$uuid}.{$kind->extension()}", [
            'ContentType' => $kind->contentType(),
        ]);

        if ($path === false || $sha256 === false) {
            throw new RuntimeException("Unable to store release {$uuid}.");
        }

        try {
            return AppRelease::query()->create([
                'uuid' => $uuid,
                'project' => $this->catalog->project(),
                'platform' => 'android',
                'channel' => ReleaseChannel::fromInput($attributes['channel'] ?? null),
                'kind' => $kind,
                'fingerprint' => $attributes['fingerprint'],
                'version' => $attributes['version'],
                'version_code' => $kind === ReleaseKind::Apk ? (int) ($attributes['version_code'] ?? 0) : null,
                'notes' => $attributes['notes'] ?? null,
                'sha256' => $sha256,
                'size' => $size,
                'path' => $path,
                'native_signature' => isset($attributes['native_signature']) ? strtolower($attributes['native_signature']) : null,
                'mandatory' => (bool) ($attributes['mandatory'] ?? false),
                'is_active' => true,
                'published_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }
    }
}
