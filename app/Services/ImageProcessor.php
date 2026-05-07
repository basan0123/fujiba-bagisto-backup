<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Normalises any uploaded image (jpeg, png, gif, bmp, webp, svg, ico, tiff,
 * heic/heif, avif, raw camera dumps) into a web-friendly WebP at a sane
 * maximum size. Used by every product / category / channel / receipt /
 * profile upload path so the cashier or admin can drop in whatever the
 * phone produced and the storefront still loads quickly.
 *
 * Strategy:
 *   1. Try Imagick first — it understands HEIC/HEIF, AVIF, RAW formats,
 *      and large multi-page images via libvips/ImageMagick delegates.
 *   2. Fall back to GD (always available, supports jpg/png/gif/webp/bmp).
 *   3. SVG passes through untouched (vector — already small, can't be
 *      raster-resized without rasterising).
 *
 * Output: WebP, max 2048x2048, quality 82. Originals are never kept.
 */
class ImageProcessor
{
    /**
     * Maximum width or height of the output image. Larger uploads are
     * downscaled preserving aspect ratio. 2048 covers retina hero shots
     * without bloating storage.
     */
    public const MAX_DIMENSION = 2048;

    /**
     * WebP quality for lossy encode. 82 is the sweet-spot recommended by
     * Google's WebP team for photographic content.
     */
    public const WEBP_QUALITY = 82;

    /**
     * Encode an arbitrary UploadedFile to a WebP byte string.
     *
     * Returns ['data' => binary, 'extension' => 'webp'|'svg'] on success,
     * or null when the file isn't an image we can decode (caller should
     * fall back to storing the file as-is or rejecting it).
     */
    public function processToWebp(UploadedFile $file): ?array
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return null;
        }

        // SVG: vector, pass through as-is. Resizing a vector is a no-op
        // and rasterising would lose the very property that makes SVG
        // useful for logos / icons.
        $mime = strtolower((string) $file->getMimeType());
        $ext  = strtolower((string) $file->getClientOriginalExtension());
        if ($mime === 'image/svg+xml' || $ext === 'svg' || $ext === 'svgz') {
            return [
                'data'      => file_get_contents($path),
                'extension' => 'svg',
                'mime'      => 'image/svg+xml',
            ];
        }

        // Try Imagick first — handles HEIC/AVIF/TIFF/RAW that GD doesn't.
        if (extension_loaded('imagick')) {
            $bytes = $this->encodeWithImagick($path);
            if ($bytes !== null) {
                return ['data' => $bytes, 'extension' => 'webp', 'mime' => 'image/webp'];
            }
        }

        // GD fallback — covers the common JPG/PNG/GIF/WebP/BMP path.
        $bytes = $this->encodeWithGd($path);
        if ($bytes !== null) {
            return ['data' => $bytes, 'extension' => 'webp', 'mime' => 'image/webp'];
        }

        return null;
    }

    /**
     * Process via Imagick — first-frame only for animated/multi-page,
     * downscaled, stripped of EXIF, encoded to WebP.
     */
    private function encodeWithImagick(string $path): ?string
    {
        try {
            $img = new \Imagick();
            // Tell Imagick to read just the first frame for HEIC/GIF/TIFF.
            $img->readImage($path . '[0]');
            $img->setImageBackgroundColor('white');
            $img = $img->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

            // Auto-rotate based on EXIF before stripping it.
            $this->autoOrientImagick($img);
            $img->stripImage();

            $w = $img->getImageWidth();
            $h = $img->getImageHeight();
            if ($w > self::MAX_DIMENSION || $h > self::MAX_DIMENSION) {
                $img->thumbnailImage(self::MAX_DIMENSION, self::MAX_DIMENSION, true);
            }

            $img->setImageFormat('webp');
            $img->setImageCompressionQuality(self::WEBP_QUALITY);
            $img->setOption('webp:method', '6');

            $bytes = $img->getImageBlob();
            $img->clear();
            $img->destroy();

            return $bytes ?: null;
        } catch (\Throwable $e) {
            Log::warning('ImageProcessor: Imagick failed', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Apply EXIF orientation so iPhone photos don't render sideways.
     */
    private function autoOrientImagick(\Imagick $img): void
    {
        try {
            $orientation = $img->getImageOrientation();
            switch ($orientation) {
                case \Imagick::ORIENTATION_BOTTOMRIGHT:
                    $img->rotateImage('#fff', 180);
                    break;
                case \Imagick::ORIENTATION_RIGHTTOP:
                    $img->rotateImage('#fff', 90);
                    break;
                case \Imagick::ORIENTATION_LEFTBOTTOM:
                    $img->rotateImage('#fff', -90);
                    break;
            }
            $img->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
        } catch (\Throwable) {
            // Orientation is best-effort; some formats lack EXIF entirely.
        }
    }

    /**
     * GD fallback — handles JPG/PNG/GIF/WebP/BMP. Returns webp bytes.
     */
    private function encodeWithGd(string $path): ?string
    {
        try {
            $info = @getimagesize($path);
            if (! $info) {
                return null;
            }

            $src = match ($info[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
                IMAGETYPE_PNG  => @imagecreatefrompng($path),
                IMAGETYPE_GIF  => @imagecreatefromgif($path),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
                IMAGETYPE_BMP  => function_exists('imagecreatefrombmp')  ? @imagecreatefrombmp($path)  : null,
                default        => null,
            };

            if (! $src) {
                return null;
            }

            // Apply EXIF orientation for JPEG (GD doesn't auto-rotate).
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $src = $this->autoOrientGd($src, $path);
            }

            $w = imagesx($src);
            $h = imagesy($src);

            $maxDim = self::MAX_DIMENSION;
            if ($w > $maxDim || $h > $maxDim) {
                $ratio = min($maxDim / $w, $maxDim / $h);
                $newW  = (int) round($w * $ratio);
                $newH  = (int) round($h * $ratio);
                $dst   = imagecreatetruecolor($newW, $newH);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }

            ob_start();
            imagewebp($src, null, self::WEBP_QUALITY);
            $bytes = ob_get_clean();
            imagedestroy($src);

            return $bytes ?: null;
        } catch (\Throwable $e) {
            Log::warning('ImageProcessor: GD failed', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Rotate a GD image based on EXIF orientation.
     *
     * @param  resource|\GdImage  $src
     * @return resource|\GdImage
     */
    private function autoOrientGd($src, string $path)
    {
        try {
            $exif = @exif_read_data($path);
            if (! $exif || empty($exif['Orientation'])) {
                return $src;
            }
            return match ((int) $exif['Orientation']) {
                3       => imagerotate($src, 180, 0),
                6       => imagerotate($src, -90, 0),
                8       => imagerotate($src,  90, 0),
                default => $src,
            };
        } catch (\Throwable) {
            return $src;
        }
    }

    /**
     * Best-effort sniff: is this an image at all? Used by RelaxedImage
     * validation rule. Returns true for SVG too.
     */
    public function looksLikeImage(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        // Some HEIC files arrive as application/octet-stream; fall back
        // to extension sniff.
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $imageExts = [
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'svgz',
            'ico', 'tif', 'tiff', 'heic', 'heif', 'avif',
        ];

        return in_array($ext, $imageExts, true);
    }
}
