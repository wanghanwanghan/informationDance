<?php

namespace App\HttpController\Service\Imagick;

use EasySwoole\Component\Singleton;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImagickService
{
    use Singleton;

    private $driver = 'imagick';

    private function __construct()
    {
        if (!extension_loaded('imagick')) $this->driver = 'gd';
    }

    function getImagickByPic($filePath): Image
    {
        return (new ImageManager([
            'driver' => $this->driver
        ]))->make($filePath);
    }

    function getEmptyImagick($width, $heicht, $bgColor = null): Image
    {
        return (new ImageManager([
            'driver' => $this->driver
        ]))->canvas($width, $heicht, $bgColor);
    }

    function cut($width, $height, $w, $h): int
    {
        if (empty($h)) {
            $h = $height * $w / $width;
        } else {
            $w = $width * $h / $height;
        }

        $w > $h ? $num = $h : $num = $w;

        return intval($num);
    }

    function colorInverse($color): string
    {
        $color = str_replace('#', '', $color);
        $rgb = '#';
        for ($x = 0; $x < 3; $x++) {
            $c = 255 - hexdec(substr($color, (2 * $x), 2));
            $c = ($c < 0) ? 0 : dechex($c);
            $rgb .= (strlen($c) < 2) ? '0' . $c : $c;
        }
        return $rgb;
    }

}
