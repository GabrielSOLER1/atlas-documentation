<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Avatar
{
    public function normalize(mixed $encoded): string
    {
        if (!is_string($encoded) || strlen($encoded) > 350000) { throw new BadRequestHttpException(); }
        $bytes = base64_decode($encoded, true);
        if (!$bytes || strlen($bytes) > 262144) { throw new BadRequestHttpException(); }
        $size = @getimagesizefromstring($bytes);
        if (!$size || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 8000000) {
            throw new BadRequestHttpException();
        }
        $source = @imagecreatefromstring($bytes);
        if (!$source) { throw new BadRequestHttpException(); }
        $target = imagecreatetruecolor(256, 256);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $edge = min($size[0], $size[1]);
        imagecopyresampled($target, $source, 0, 0, (int) (($size[0] - $edge) / 2), (int) (($size[1] - $edge) / 2), 256, 256, $edge, $edge);
        ob_start();
        imagewebp($target, null, 85);
        $result = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);
        return $result;
    }
}
