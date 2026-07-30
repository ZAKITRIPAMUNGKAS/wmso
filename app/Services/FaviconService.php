<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FaviconService
{
    /**
     * Generate all favicon files from the given source image path.
     * The source path is relative to the storage/public disk
     * (e.g. "company/abc123.png").
     *
     * @param  string  $storagePath  Path relative to storage/app/public
     * @return bool  true on success, false on failure
     */
    public function generateFromStoragePath(string $storagePath): bool
    {
        $absolutePath = storage_path('app/public/' . $storagePath);
        return $this->generateFromAbsolutePath($absolutePath);
    }

    /**
     * Generate all favicon files from an absolute image path.
     *
     * @param  string  $absolutePath  Absolute path to the source image
     * @return bool
     */
    public function generateFromAbsolutePath(string $absolutePath): bool
    {
        if (!file_exists($absolutePath)) {
            Log::warning("FaviconService: source image not found at {$absolutePath}");
            return false;
        }

        try {
            $src = $this->loadImage($absolutePath);
            if (!$src) {
                Log::warning("FaviconService: could not load image from {$absolutePath}");
                return false;
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);

            $publicPath = public_path();

            // 1. favicon-16x16.png
            $this->resizeAndSave($src, $srcW, $srcH, 16, "{$publicPath}/favicon-16x16.png");

            // 2. favicon-32x32.png
            $this->resizeAndSave($src, $srcW, $srcH, 32, "{$publicPath}/favicon-32x32.png");

            // 3. favicon-192x192.png (used in <link> tag and android-chrome)
            $this->resizeAndSave($src, $srcW, $srcH, 192, "{$publicPath}/favicon-192x192.png");

            // 4. apple-touch-icon.png (180x180)
            $this->resizeAndSave($src, $srcW, $srcH, 180, "{$publicPath}/apple-touch-icon.png");

            // 5. android-chrome variants
            copy("{$publicPath}/favicon-192x192.png", "{$publicPath}/android-chrome-192x192.png");
            $this->resizeAndSave($src, $srcW, $srcH, 512, "{$publicPath}/android-chrome-512x512.png");

            // 6. logo.png (full-size copy for reference)
            copy($absolutePath, "{$publicPath}/logo.png");

            // 7. favicon.ico (multi-size: 16, 32, 48)
            $img48 = $this->createResized($src, $srcW, $srcH, 48);
            $this->createIco(
                [
                    ['size' => 16, 'path' => "{$publicPath}/favicon-16x16.png"],
                    ['size' => 32, 'path' => "{$publicPath}/favicon-32x32.png"],
                    ['size' => 48, 'img'  => $img48],
                ],
                "{$publicPath}/favicon.ico"
            );
            if (is_resource($img48) || (is_object($img48) && $img48 instanceof \GdImage)) {
                imagedestroy($img48);
            }

            imagedestroy($src);

            Log::info("FaviconService: all favicons generated successfully from {$absolutePath}");
            return true;

        } catch (\Throwable $e) {
            Log::error("FaviconService: failed to generate favicons — {$e->getMessage()}");
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Load an image resource from any supported format (PNG, JPG, WEBP, GIF).
     */
    private function loadImage(string $path)
    {
        $mime = mime_content_type($path);

        return match (true) {
            str_contains($mime, 'png')  => imagecreatefrompng($path),
            str_contains($mime, 'jpeg'),
            str_contains($mime, 'jpg')  => imagecreatefromjpeg($path),
            str_contains($mime, 'webp') => imagecreatefromwebp($path),
            str_contains($mime, 'gif')  => imagecreatefromgif($path),
            default => false,
        };
    }

    /**
     * Resize the source image to $size x $size and save as PNG.
     */
    private function resizeAndSave($src, int $srcW, int $srcH, int $size, string $outPath): void
    {
        $dst = $this->createResized($src, $srcW, $srcH, $size);
        imagepng($dst, $outPath, 9);
        imagedestroy($dst);
    }

    /**
     * Create a resized GD image without saving it.
     *
     * @return \GdImage|resource
     */
    private function createResized($src, int $srcW, int $srcH, int $size)
    {
        $dst = imagecreatetruecolor($size, $size);

        // Preserve transparency
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        imagealphablending($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);

        return $dst;
    }

    /**
     * Create a multi-resolution .ico file from an array of size descriptors.
     *
     * Each entry may be:
     *   ['size' => int, 'path' => string]  — read PNG from disk
     *   ['size' => int, 'img'  => resource] — use an in-memory GD image
     */
    private function createIco(array $entries, string $outputPath): void
    {
        $images = [];

        foreach ($entries as $entry) {
            $size = $entry['size'];

            if (isset($entry['img'])) {
                // In-memory GD image → capture as PNG bytes
                ob_start();
                imagepng($entry['img'], null, 0);
                $pngData = ob_get_clean();
            } else {
                $pngData = file_get_contents($entry['path']);
            }

            $images[] = ['size' => $size, 'data' => $pngData];
        }

        $count = count($images);

        // ICO header: Reserved(2) + Type=1(2) + ImageCount(2)
        $header    = pack('vvv', 0, 1, $count);
        $dirSize   = 16 * $count;
        $offset    = 6 + $dirSize;
        $directory = '';
        $imageData = '';

        foreach ($images as $img) {
            $s       = $img['size'];
            $data    = $img['data'];
            $dataLen = strlen($data);
            $w       = $s >= 256 ? 0 : $s;
            $h       = $s >= 256 ? 0 : $s;

            // ICONDIRENTRY: Width, Height, ColorCount, Reserved, Planes, BitCount, BytesInRes, ImageOffset
            $directory .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $dataLen, $offset);
            $imageData .= $data;
            $offset    += $dataLen;
        }

        file_put_contents($outputPath, $header . $directory . $imageData);
    }
}
