<?php
declare(strict_types=1);

namespace App\Model;

use App\Entity\Packaging;

interface PackingSolver
{
    /**
     * Pack multiple items in a single box.
     * @param Item[] $items
     * @param Packaging[] $packagings
     * @throws SolutionNotFoundException
     */
    public function pack(iterable $items, iterable $packagings): Box;
}
