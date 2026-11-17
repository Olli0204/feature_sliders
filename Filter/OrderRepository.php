<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\Filter\ProductFilter;

/**
 * Standard order of the characteristic filters (backend tab "Merkmale", section "Reihenfolge der Filter").
 * Applies on every listing page, also in categories with a filter group (the group only decides which
 * characteristics appear). Characteristics without an entry follow behind, in the order the core delivered them
 * (Wawi sort order).
 */
final class OrderRepository
{
    public const TABLE = 'feature_sliders_order';

    private const CACHE_ID = 'feature_sliders_order';

    public function __construct(
        private readonly DbInterface $db,
        private readonly ?JTLCacheInterface $cache = null,
        private readonly string $cacheGroup = ''
    ) {
    }

    /**
     * @return int[] kMerkmal in configured order (empty = no standard order, core order stays)
     */
    public function getOrder(): array
    {
        $ids = $this->cache?->get(self::CACHE_ID);
        if (!\is_array($ids)) {
            try {
                $ids = \array_map(
                    static fn($row): int => (int)$row->kMerkmal,
                    $this->db->getObjects('SELECT kMerkmal FROM ' . self::TABLE . ' ORDER BY sort, kMerkmal')
                );
            } catch (\Throwable) {
                // table missing (migration not run yet)
                return [];
            }
            $this->cache?->set(self::CACHE_ID, $ids, \array_filter([$this->cacheGroup]));
        }

        return $ids;
    }

    /**
     * @param int[] $ids new complete order
     */
    public function save(array $ids): void
    {
        $ids = \array_values(\array_unique(\array_filter(\array_map('\intval', $ids), static fn(int $id): bool => $id > 0)));
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE, []);
        foreach ($ids as $i => $id) {
            $this->db->insert(self::TABLE, (object)['kMerkmal' => $id, 'sort' => $i + 1]);
        }
        $this->flush();
    }

    public function reset(): void
    {
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE, []);
        $this->flush();
    }

    /**
     * Stable sort: ordered characteristics first (configured position), all others behind in their current order.
     *
     * @template T of object
     * @param T[] $options objects with getValue() = kMerkmal
     * @param int[] $order
     * @return T[]
     */
    public static function sortOptions(array $options, array $order): array
    {
        if (\count($order) === 0 || \count($options) < 2) {
            return \array_values($options);
        }
        $position = \array_flip($order);
        $keyed    = [];
        foreach (\array_values($options) as $i => $option) {
            $id      = (int)$option->getValue();
            $keyed[] = [$position[$id] ?? (\PHP_INT_MAX >> 1), $i, $option];
        }
        \usort($keyed, static fn(array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return \array_column($keyed, 2);
    }

    /**
     * Sorts the characteristic filter of the current listing (sidebar box and filter modal read the same options).
     *
     * @param int[] $order
     */
    public static function apply(ProductFilter $productFilter, array $order): void
    {
        if (\count($order) === 0) {
            return;
        }
        $results = $productFilter->getSearchResults();
        $options = $results->getCharacteristicFilterOptions();
        if (\count($options) < 2) {
            return;
        }
        $sorted     = self::sortOptions($options, $order);
        $collection = $productFilter->getCharacteristicFilterCollection();
        $results->setCharacteristicFilterOptions($sorted);
        $collection->setOptions($sorted);
        $collection->setFilterCollection($sorted);
    }

    public function flush(): void
    {
        if ($this->cache === null) {
            return;
        }
        $this->cache->flush(self::CACHE_ID);
        if ($this->cacheGroup !== '') {
            $this->cache->flushTags([$this->cacheGroup]);
        }
    }
}
