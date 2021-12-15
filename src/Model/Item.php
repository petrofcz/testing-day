<?php
declare(strict_types=1);

namespace App\Model;

class Item
{
    protected string $id;
    protected float $width;
    protected float $height;
    protected float $length;
    protected float $weight;

    public function __construct(string $id, float $width, float $height, float $length, float $weight)
    {
        $this->id = $id;
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->weight = $weight;
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getWeight(): float
    {
        return $this->weight;
    }
}
