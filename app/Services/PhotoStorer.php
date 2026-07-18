<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\Submission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Imagick;

/**
 * Re-encodes a photo on intake and banks it against its recording. Only the
 * re-encode (plus a thumbnail) is ever stored — JPEG, capped long edge, EXIF
 * stripped — and the uploaded original is discarded (docs/adr/0003). A photo
 * that can't be processed is skipped, never fatal: the memo is the thing
 * being submitted, the photos ride along.
 */
class PhotoStorer
{
    public function attach(Submission $submission, string $bytes, ?string $originalFilename = null): ?Photo
    {
        $config = config('pipeline.photos');

        if ($submission->photos()->count() >= $config['max_per_submission']) {
            Log::info('Photo cap reached for submission — extra photo skipped.', [
                'submission_id' => $submission->id,
            ]);

            return null;
        }

        try {
            [$display, $thumb] = $this->reencode($bytes, $config);
        } catch (\Throwable $e) {
            Log::warning('Could not process a photo — skipped.', [
                'submission_id' => $submission->id,
                'filename' => $originalFilename,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $base = $config['path'].'/'.Str::uuid();
        $disk = Photo::storage();
        $disk->put($path = $base.'.jpg', $display);
        $disk->put($thumbPath = $base.'_thumb.jpg', $thumb);

        return $submission->photos()->create([
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_filename' => $originalFilename,
        ]);
    }

    /**
     * @return array{0: string, 1: string} display JPEG bytes, thumbnail JPEG bytes
     */
    protected function reencode(string $bytes, array $config): array
    {
        $image = new Imagick;
        $image->readImageBlob($bytes);

        // Bake the EXIF rotation into the pixels *before* stripping the tag
        // that records it, or portrait phone shots would land on their side.
        $image->autoOrient();

        // Converting to sRGB first keeps colours faithful once stripImage()
        // throws away the ICC profile along with the EXIF (and its GPS).
        $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        $image->stripImage();

        $display = $this->encodeJpeg(clone $image, $config['max_edge'], $config['quality']);
        $thumb = $this->encodeJpeg($image, $config['thumb_edge'], $config['quality']);

        return [$display, $thumb];
    }

    protected function encodeJpeg(Imagick $image, int $maxEdge, int $quality): string
    {
        if (max($image->getImageWidth(), $image->getImageHeight()) > $maxEdge) {
            $image->resizeImage($maxEdge, $maxEdge, Imagick::FILTER_LANCZOS, 1, bestfit: true);
        }

        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality($quality);

        $encoded = $image->getImageBlob();
        $image->clear();

        return $encoded;
    }
}
