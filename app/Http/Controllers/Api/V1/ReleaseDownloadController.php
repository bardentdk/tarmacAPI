<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\Ota\ReleaseCatalog;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReleaseDownloadController extends Controller
{
    /**
     * Serve a release file behind a signed, expiring URL.
     *
     * Object storage (S3 / Laravel Cloud buckets) gets a redirect to a pre-signed
     * URL; local disks are streamed through the app.
     */
    public function __invoke(ReleaseCatalog $catalog, string $uuid): JsonResponse|RedirectResponse|StreamedResponse
    {
        $release = AppRelease::query()->active()->where('uuid', strtolower($uuid))->first();

        if ($release === null) {
            return response()->json(['message' => 'Version introuvable.'], 404);
        }

        $diskName = (string) config('tarmac.ota.disk');

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        if (config("filesystems.disks.{$diskName}.driver") !== 'local' && $disk->providesTemporaryUrls()) {
            return redirect()->away($disk->temporaryUrl(
                $release->path,
                now()->addMinutes($catalog->downloadTtlMinutes()),
            ));
        }

        if (! $disk->exists($release->path)) {
            return response()->json(['message' => 'Version introuvable.'], 404);
        }

        return $disk->download($release->path, $release->downloadName(), [
            'Content-Type' => $release->kind->contentType(),
        ]);
    }
}
