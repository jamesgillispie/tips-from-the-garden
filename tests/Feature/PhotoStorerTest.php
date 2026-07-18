<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use App\Services\PhotoStorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Tests\TestCase;

class PhotoStorerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('pipeline.photos.disk'));
    }

    public function test_a_photo_is_reencoded_to_a_capped_jpeg_with_a_thumbnail(): void
    {
        $submission = $this->makeSubmission();

        // A PNG wider than the display cap — must come back as a JPEG at 2000px.
        $photo = app(PhotoStorer::class)->attach($submission, $this->imageBytes(2400, 1200, 'png'), 'bed-three.png');

        $this->assertNotNull($photo);
        $this->assertSame('bed-three.png', $photo->original_filename);
        Storage::disk(config('pipeline.photos.disk'))->assertExists([$photo->path, $photo->thumb_path]);

        [$width, $height, $format] = $this->storedImageInfo($photo->path);
        $this->assertSame('JPEG', $format);
        $this->assertSame([2000, 1000], [$width, $height]);

        [$width, $height, $format] = $this->storedImageInfo($photo->thumb_path);
        $this->assertSame('JPEG', $format);
        $this->assertSame([640, 320], [$width, $height]);
    }

    public function test_a_small_photo_is_not_upscaled(): void
    {
        $submission = $this->makeSubmission();

        $photo = app(PhotoStorer::class)->attach($submission, $this->imageBytes(500, 400, 'jpeg'));

        [$width, $height] = $this->storedImageInfo($photo->path);
        $this->assertSame([500, 400], [$width, $height]);
    }

    public function test_exif_metadata_is_stripped_from_both_stored_copies(): void
    {
        $submission = $this->makeSubmission();

        // A JPEG carrying camera make + GPS EXIF — the exact data ADR 0003
        // exists to keep out of storage.
        $bytes = $this->withExif($this->imageBytes(300, 100, 'jpeg'), $this->exifApp1(
            [$this->tiffEntry(0x010F, 2, 4, "ACM\x00")], // Make = "ACM"
            [$this->tiffEntry(0x0001, 2, 2, "N\x00")],   // GPSLatitudeRef = "N"
        ));

        // Precondition: the fixture really does carry EXIF.
        $fixture = new Imagick;
        $fixture->readImageBlob($bytes);
        $this->assertNotEmpty($fixture->getImageProperties('exif:*'));

        $photo = app(PhotoStorer::class)->attach($submission, $bytes);

        foreach ([$photo->path, $photo->thumb_path] as $path) {
            $stored = new Imagick;
            $stored->readImageBlob(Storage::disk(config('pipeline.photos.disk'))->get($path));
            $this->assertSame([], $stored->getImageProperties('exif:*'), "EXIF survived in {$path}");
        }
    }

    public function test_exif_orientation_is_applied_before_it_is_stripped(): void
    {
        $submission = $this->makeSubmission();

        // Orientation 6 = rotate 90° clockwise: a 300x100 frame is really
        // a 100x300 portrait shot. Stripping without rotating first would
        // leave every phone photo lying on its side.
        $bytes = $this->withExif($this->imageBytes(300, 100, 'jpeg'), $this->exifApp1(
            [$this->tiffEntry(0x0112, 3, 1, pack('v', 6)."\x00\x00")],
        ));

        $photo = app(PhotoStorer::class)->attach($submission, $bytes);

        [$width, $height] = $this->storedImageInfo($photo->path);
        $this->assertSame([100, 300], [$width, $height]);
    }

    public function test_unreadable_bytes_are_skipped_without_throwing(): void
    {
        $submission = $this->makeSubmission();

        $photo = app(PhotoStorer::class)->attach($submission, 'not an image at all');

        $this->assertNull($photo);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk(config('pipeline.photos.disk'))->allFiles());
    }

    public function test_a_submission_never_holds_more_than_the_photo_cap(): void
    {
        config(['pipeline.photos.max_per_submission' => 2]);

        $submission = $this->makeSubmission();
        $storer = app(PhotoStorer::class);

        $this->assertNotNull($storer->attach($submission, $this->imageBytes(100, 100, 'jpeg')));
        $this->assertNotNull($storer->attach($submission, $this->imageBytes(100, 100, 'jpeg')));
        $this->assertNull($storer->attach($submission, $this->imageBytes(100, 100, 'jpeg')));

        $this->assertSame(2, $submission->photos()->count());
    }

    private function makeSubmission(): Submission
    {
        return Submission::create([
            'user_id' => User::fromEmail('gardener@example.test')->id,
            'source' => Submission::SOURCE_RECORD,
            'audio_path' => 'audio/fake.webm',
        ]);
    }

    /** @return array{0: int, 1: int, 2: string} width, height, format */
    private function storedImageInfo(string $path): array
    {
        $image = new Imagick;
        $image->readImageBlob(Storage::disk(config('pipeline.photos.disk'))->get($path));

        return [$image->getImageWidth(), $image->getImageHeight(), $image->getImageFormat()];
    }

    private function imageBytes(int $width, int $height, string $format): string
    {
        $image = new Imagick;
        $image->newImage($width, $height, 'green');
        $image->setImageFormat($format);

        return $image->getImageBlob();
    }

    /**
     * Build a minimal EXIF APP1 segment (little-endian TIFF: IFD0 entries,
     * plus an optional GPS IFD) so fixtures carry real, parseable EXIF.
     */
    private function exifApp1(array $ifd0Entries, ?array $gpsEntries = null): string
    {
        $ifd0Count = count($ifd0Entries) + ($gpsEntries !== null ? 1 : 0);
        $gpsOffset = 8 + (2 + $ifd0Count * 12 + 4); // offsets count from the TIFF header

        $ifd0 = pack('v', $ifd0Count).implode('', $ifd0Entries);
        if ($gpsEntries !== null) {
            $ifd0 .= $this->tiffEntry(0x8825, 4, 1, pack('V', $gpsOffset)); // GPSInfo pointer
        }
        $ifd0 .= pack('V', 0);

        $tiff = 'II'.pack('v', 0x2A).pack('V', 8).$ifd0;

        if ($gpsEntries !== null) {
            $tiff .= pack('v', count($gpsEntries)).implode('', $gpsEntries).pack('V', 0);
        }

        $payload = "Exif\x00\x00".$tiff;

        return "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    }

    private function tiffEntry(int $tag, int $type, int $count, string $value4): string
    {
        return pack('v', $tag).pack('v', $type).pack('V', $count).str_pad(substr($value4, 0, 4), 4, "\x00");
    }

    /** Splice an APP1 segment into a JPEG right after the SOI marker. */
    private function withExif(string $jpeg, string $app1): string
    {
        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }
}
