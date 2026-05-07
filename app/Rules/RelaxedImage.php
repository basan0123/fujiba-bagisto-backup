<?php

namespace App\Rules;

use App\Services\ImageProcessor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Drop-in replacement for `mimes:bmp,jpeg,jpg,png,webp` that accepts any
 * format the ImageProcessor service can decode (JPG, PNG, GIF, BMP, WebP,
 * SVG, ICO, TIFF, HEIC/HEIF, AVIF). The processor downscales and converts
 * to WebP on save, so storage stays consistent regardless of input.
 *
 * No size cap — large originals are scaled down to ImageProcessor::MAX_DIMENSION
 * during processing.
 */
class RelaxedImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a valid uploaded file.');
            return;
        }

        if (! $value->isValid()) {
            $fail('The :attribute upload failed.');
            return;
        }

        $processor = app(ImageProcessor::class);

        if (! $processor->looksLikeImage($value)) {
            $fail('The :attribute must be an image (JPG, PNG, GIF, WebP, BMP, SVG, HEIC, AVIF, etc).');
        }
    }
}
