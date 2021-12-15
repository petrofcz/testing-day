<?php
declare(strict_types=1);

namespace App\Model;

class Box
{
    private float $width;
    private float $height;
    private float $length;

    public function __construct(float $width, float $height, float $length)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getLength(): float
    {
        return $this->length;
    }
}
