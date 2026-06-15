<?php

namespace App\Support;

/**
 * Minimal GD-based image downscaler used for admin logo uploads. Scales a raster
 * image to fit within a bounding box (preserving aspect + transparency) and
 * writes a PNG. SVGs are vector and copied untouched. No Composer dependency.
 */
class ImageResizer
{
    /**
     * Fit $src within $max x $max and write a PNG to $dest.
     * Returns true on success; false if GD couldn't read the source.
     */
    public static function fitToPng(string $src, string $dest, int $max = 256): bool
    {
        if (! function_exists('imagecreatetruecolor')) {
            return copy($src, $dest); // GD missing: store the original as a fallback
        }

        $info = @getimagesize($src);
        if (! $info) {
            return false;
        }
        [$w, $h] = $info;

        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
            IMAGETYPE_PNG => @imagecreatefrompng($src),
            IMAGETYPE_GIF => @imagecreatefromgif($src),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null,
            default => null,
        };
        if (! $img) {
            return false;
        }

        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $ok = imagepng($dst, $dest);
        imagedestroy($img);
        imagedestroy($dst);

        return $ok;
    }
}
