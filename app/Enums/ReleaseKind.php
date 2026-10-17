<?php

namespace App\Enums;

/**
 * What a published app release contains.
 */
enum ReleaseKind: string
{
    /** Over-the-air bundle of the PHP layer, installed by the OTA plugin. */
    case Ota = 'ota';

    /** Full native Android package (new shell). */
    case Apk = 'apk';

    /**
     * File extension of the stored artifact.
     */
    public function extension(): string
    {
        return match ($this) {
            self::Ota => 'zip',
            self::Apk => 'apk',
        };
    }

    /**
     * Content-Type served when the artifact is downloaded.
     */
    public function contentType(): string
    {
        return match ($this) {
            self::Ota => 'application/zip',
            self::Apk => 'application/vnd.android.package-archive',
        };
    }

    /**
     * All raw values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
