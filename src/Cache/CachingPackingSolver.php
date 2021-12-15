<?php
declare(strict_types=1);

namespace App\Cache;

use App\Model\Box;
use App\Model\PackingSolver;
use Psr\Cache\CacheItemPoolInterface;

class CachingPackingSolver implements PackingSolver
{
    private PackingSolver $packingSolver;
    private CacheItemPoolInterface $cachePool;
    private ItemsCacheKeyProvider $itemsCacheKeyProvider;
    private PackagingsCacheKeyProvider $packagingsCacheKeyProvider;

    public function __construct(
        PackingSolver $packingSolver,
        CacheItemPoolInterface $cachePool,
        ItemsCacheKeyProvider $itemsCacheKeyProvider,
        PackagingsCacheKeyProvider $packagingsCacheKeyProvider
    ) {
        $this->packingSolver = $packingSolver;
        $this->cachePool = $cachePool;
        $this->itemsCacheKeyProvider = $itemsCacheKeyProvider;
        $this->packagingsCacheKeyProvider = $packagingsCacheKeyProvider;
    }

    public function pack(iterable $items, iterable $packagings): Box
    {
        $itemsCacheKey = $this->itemsCacheKeyProvider->getCacheKey($items);
        $packagingsCacheKey = $this->itemsCacheKeyProvider->getCacheKey($packagings);

        $key = $itemsCacheKey . $packagingsCacheKey;

        $cacheItem = $this->cachePool->getItem($key);
        if($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $box = $this->packingSolver->pack($items, $packagings);
        $cacheItem->set($box);

        return $box;
    }
}
