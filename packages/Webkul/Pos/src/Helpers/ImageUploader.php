<?php

namespace Webkul\Pos\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\GraphQLAPI\Validators\CustomException;

/**
 * Base64 image uploader for POS mutations. Validates type + size,
 * normalises to JPEG/PNG, writes to storage/app/public/<subdir>/<hash>.<ext>,
 * returns a public URL.
 *
 * Sprint product-edit (2026-05-11): 2MB max, JPG/PNG only, server-side
 * resize to 1024px max edge. We use Intervention/Image which is already
 * a Bagisto dependency.
 */
class ImageUploader
{
    public const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    public const MAX_EDGE  = 1024;

    /**
     * Decode + persist a base64 image; returns ['path' => 'products/abc.jpg', 'url' => 'https://...'].
     */
    public static function storeBase64(?string $base64, ?string $name, string $subdir = 'products'): array
    {
        if (empty($base64)) {
            throw new CustomException(trans('pos::app.outlet.upload.empty'));
        }

        // Allow `data:image/jpeg;base64,...` prefix or raw base64.
        $payload = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $base64);
        $bytes = base64_decode($payload, true);
        if ($bytes === false || strlen($bytes) === 0) {
            throw new CustomException(trans('pos::app.outlet.upload.bad-encoding'));
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new CustomException(trans('pos::app.outlet.upload.too-large', ['limit' => '2 MB']));
        }

        $mime = static::detectMime($bytes);
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new CustomException(trans('pos::app.outlet.upload.bad-type'));
        }

        // Resize via Intervention if larger than MAX_EDGE.
        try {
            $img = \Image::make($bytes);
            if (max($img->width(), $img->height()) > self::MAX_EDGE) {
                $img->resize(self::MAX_EDGE, self::MAX_EDGE, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                });
            }
            $ext = $mime === 'image/png' ? 'png' : 'jpg';
            $bytes = (string) $img->encode($ext, 88);
        } catch (\Throwable $e) {
            throw new CustomException(trans('pos::app.outlet.upload.decode-failed', ['msg' => $e->getMessage()]));
        }

        $hash = substr(hash('sha256', $bytes), 0, 24);
        $ext  = $mime === 'image/png' ? 'png' : 'jpg';
        $path = trim($subdir, '/') . '/' . $hash . '.' . $ext;

        Storage::disk('public')->put($path, $bytes);

        return [
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ];
    }

    protected static function detectMime(string $bytes): string
    {
        $info = @getimagesizefromstring($bytes);
        return $info['mime'] ?? 'application/octet-stream';
    }
}
