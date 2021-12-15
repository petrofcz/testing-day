<?php
declare(strict_types=1);

namespace App\Cache;

use App\Entity\Packaging;

class PackagingsCacheKeyProvider
{
    /**
     * @param Packaging[] $packagings
     * @return string
     */
    public function getCacheKey(iterable $packagings): string {
        $itemDataSequence = [];

        foreach($packagings as $item) {
            $dimensions = [
                $item->getLength(),
                $item->getHeight(),
                $item->getWidth(),
            ];
            sort($dimensions);

            $itemData = [];
            array_push($itemData, ...$dimensions);
            $itemData[] = $item->getMaxWeight();
            $itemData[] = $item->getLength() * $item->getHeight() * $item->getWidth();

            $itemDataSequence[] = $itemData;
        }

        // todo make it more readable
        // todo add other sorting props in case of tie
        // sort by volume
        sort($itemDataSequence, static fn($a, $b) => $a[4] - $b[4]);

        foreach($itemDataSequence as &$itemData) {
            unset($itemData[4]);
        }

        return serialize($itemDataSequence);
    }
}
