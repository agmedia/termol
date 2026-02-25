<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaUrl
{
    public static function conversion(?Media $media, string $conversion, bool $preferWebp = false): ?string
    {
        if (! $media) {
            return null;
        }

        $baseConversion = trim($conversion);
        if ($baseConversion === '') {
            return (string) $media->getUrl();
        }

        $webpConversion = $baseConversion.'_webp';

        if ($preferWebp && $media->hasGeneratedConversion($webpConversion)) {
            return (string) $media->getUrl($webpConversion);
        }

        if ($media->hasGeneratedConversion($baseConversion)) {
            return (string) $media->getUrl($baseConversion);
        }

        if ($preferWebp && $media->hasGeneratedConversion($webpConversion)) {
            return (string) $media->getUrl($webpConversion);
        }

        return (string) $media->getUrl();
    }
}

