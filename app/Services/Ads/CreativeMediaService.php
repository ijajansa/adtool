<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CreativeMediaService
{
    /** @return array<string, mixed> */
    public function store(AdCampaign $campaign, UploadedFile $file, string $format): array
    {
        $extension = strtolower($file->guessExtension() ?: 'bin');
        $directory = "ads/business-{$campaign->business_id}/campaign-{$campaign->id}";
        $path = $directory.'/'.Str::uuid().'.'.$extension;

        if (! Storage::disk('local')->putFileAs($directory, $file, basename($path))) {
            throw new RuntimeException('The advertisement media could not be stored.');
        }

        [$width, $height] = $format === 'single_image' ? $this->imageDimensions($file) : [null, null];
        $thumbnail = $format === 'single_image' ? $this->createImageThumbnail($path, $extension) : null;

        return [
            'media_path' => $path,
            'original_filename' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'thumbnail_path' => $thumbnail,
        ];
    }

    /** @return array{media_path: string, thumbnail_path: ?string} */
    public function copy(AdCampaign $target, string $mediaPath, ?string $thumbnailPath): array
    {
        $directory = "ads/business-{$target->business_id}/campaign-{$target->id}";
        $newMedia = $directory.'/'.Str::uuid().'.'.pathinfo($mediaPath, PATHINFO_EXTENSION);
        if (! Storage::disk('local')->copy($mediaPath, $newMedia)) {
            throw new RuntimeException('The creative media could not be copied.');
        }

        $newThumbnail = null;
        if ($thumbnailPath && Storage::disk('local')->exists($thumbnailPath)) {
            $newThumbnail = $directory.'/'.Str::uuid().'.'.pathinfo($thumbnailPath, PATHINFO_EXTENSION);
            if (! Storage::disk('local')->copy($thumbnailPath, $newThumbnail)) {
                Storage::disk('local')->delete($newMedia);
                throw new RuntimeException('The creative thumbnail could not be copied.');
            }
        }

        return ['media_path' => $newMedia, 'thumbnail_path' => $newThumbnail];
    }

    public function delete(?string ...$paths): void
    {
        Storage::disk('local')->delete(array_values(array_filter($paths)));
    }

    private function imageDimensions(UploadedFile $file): array
    {
        $size = @getimagesize($file->getRealPath());

        return $size ? [$size[0], $size[1]] : [null, null];
    }

    private function createImageThumbnail(string $sourcePath, string $extension): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $contents = Storage::disk('local')->get($sourcePath);
        $source = @imagecreatefromstring($contents);
        if (! $source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $ratio = min(600 / max($sourceWidth, 1), 600 / max($sourceHeight, 1), 1);
        $width = max(1, (int) round($sourceWidth * $ratio));
        $height = max(1, (int) round($sourceHeight * $ratio));
        $thumb = imagecreatetruecolor($width, $height);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        ob_start();
        imagewebp($thumb, null, 82);
        $thumbnailContents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($thumb);

        if (! is_string($thumbnailContents)) {
            return null;
        }

        $path = dirname($sourcePath).'/'.Str::uuid().'-thumb.webp';

        return Storage::disk('local')->put($path, $thumbnailContents) ? $path : null;
    }
}
