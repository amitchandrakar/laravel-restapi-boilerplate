<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;

/**
 * Shared GD → WebP scaling helpers for profile images and ID verification uploads.
 */
final class GdWebpEncoder
{
    /**
     * Decode from disk, apply JPEG EXIF orientation, scale down by longest edge, encode WebP.
     */
    public static function encodeScaledWebp(string $path, int $maxEdge, int $quality): string
    {
        $binary = file_get_contents($path);

        if ($binary === false || $binary === '') {
            throw new \RuntimeException('empty file');
        }

        $im = @imagecreatefromstring($binary);

        if (!($im instanceof GdImage)) {
            throw new \RuntimeException('decode failed');
        }

        $im = self::applyExifOrientation($im, $path);
        $im = self::scaleDownMaxEdge($im, $maxEdge);

        return self::toWebpString($im, $quality);
    }

    /**
     * Square WebP: scale so the image covers {@see $edge}×{@see $edge}, then center-crop.
     */
    public static function encodeSquareCenterWebp(string $path, int $edge, int $quality): string
    {
        if ($edge < 1) {
            throw new \RuntimeException('invalid icon size');
        }

        $binary = file_get_contents($path);

        if ($binary === false || $binary === '') {
            throw new \RuntimeException('empty file');
        }

        $im = @imagecreatefromstring($binary);

        if (!($im instanceof GdImage)) {
            throw new \RuntimeException('decode failed');
        }

        $im = self::applyExifOrientation($im, $path);
        $im = self::scaleAndCropCenterSquare($im, $edge);

        return self::toWebpString($im, $quality);
    }

    private static function scaleAndCropCenterSquare(GdImage $src, int $edge): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        $scale = max($edge / $w, $edge / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $scaled = imagecreatetruecolor($nw, $nh);

        if (!($scaled instanceof GdImage)) {
            imagedestroy($src);

            throw new \RuntimeException('alloc failed');
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = (int) imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefill($scaled, 0, 0, $transparent);
        imagealphablending($scaled, true);
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $sx = (int) max(0, ($nw - $edge) / 2);
        $sy = (int) max(0, ($nh - $edge) / 2);
        $cropW = min($edge, $nw);
        $cropH = min($edge, $nh);

        $out = imagecreatetruecolor($edge, $edge);

        if (!($out instanceof GdImage)) {
            imagedestroy($scaled);

            throw new \RuntimeException('alloc failed');
        }

        imagealphablending($out, false);
        imagesavealpha($out, true);
        $transparentOut = (int) imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefill($out, 0, 0, $transparentOut);
        imagealphablending($out, true);
        imagecopy($out, $scaled, 0, 0, $sx, $sy, $cropW, $cropH);
        imagedestroy($scaled);

        return $out;
    }

    private static function applyExifOrientation(GdImage $im, string $path): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $im;
        }

        $info = @getimagesize($path);

        if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
            return $im;
        }

        $exif = @exif_read_data($path);

        if (!is_array($exif) || !isset($exif['Orientation'])) {
            return $im;
        }

        $angle = match ((int) $exif['Orientation']) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => null,
        };

        if ($angle === null) {
            return $im;
        }

        $bg = imagecolorallocatealpha($im, 0, 0, 0, 127);
        $rotated = @imagerotate($im, $angle, $bg);

        if (!($rotated instanceof GdImage)) {
            return $im;
        }

        imagedestroy($im);

        return $rotated;
    }

    private static function scaleDownMaxEdge(GdImage $src, int $maxEdge): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        if ($w <= $maxEdge && $h <= $maxEdge) {
            return $src;
        }

        $ratio = min($maxEdge / $w, $maxEdge / $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);

        if (!($dst instanceof GdImage)) {
            imagedestroy($src);

            throw new \RuntimeException('alloc failed');
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = (int) imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    private static function toWebpString(GdImage $im, int $quality): string
    {
        $q = max(0, min(100, $quality));
        ob_start();
        imagewebp($im, null, $q);
        $bin = ob_get_clean();
        imagedestroy($im);

        if (!is_string($bin) || $bin === '') {
            throw new \RuntimeException('webp encode failed');
        }

        return $bin;
    }
}
