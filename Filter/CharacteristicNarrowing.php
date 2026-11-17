<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\Filter\Join;
use JTL\Filter\ProductFilter;
use JTL\Filter\StateSQL;

/**
 * Hides characteristic values (and whole characteristics) that do not occur in the current result.
 *
 * The core computes the characteristic filter while ignoring every active characteristic filter
 * (Characteristic::getState() → getCurrentStateData(Characteristic::class)): after choosing "Warengruppe: Jacken"
 * the sidebar still lists "Bindungsgröße" with all values. The third-party plugin knm_feature_filter used to hide them;
 * since it was removed this does the same: once a characteristic value is selected, every *other* characteristic only
 * keeps the values that occur in the current result (with the real count) and disappears without any.
 * Characteristics with a selected value stay complete, so a value can be switched or (multi-select) added.
 */
final class CharacteristicNarrowing
{
    private const CACHE_PREFIX = 'fs_narrow_';

    public function __construct(
        private readonly DbInterface $db,
        private readonly ?JTLCacheInterface $cache = null
    ) {
    }

    public function apply(ProductFilter $productFilter): void
    {
        $active = $productFilter->getCharacteristicFilter();
        if (!\is_array($active) || \count($active) === 0) {
            return;
        }
        $results = $productFilter->getSearchResults();
        $options = $results->getCharacteristicFilterOptions();
        if (\count($options) === 0) {
            return;
        }
        $available = $this->availableValues($productFilter);
        if ($available === null) {
            return;
        }
        $narrowed   = self::narrow($options, $available);
        $collection = $productFilter->getCharacteristicFilterCollection();
        $results->setCharacteristicFilterOptions($narrowed);
        $collection->setOptions($narrowed);
        $collection->setFilterCollection($narrowed);
    }

    /**
     * @template T of object
     * @param T[]             $options   characteristic options (getOptions() = values with getID() = kMerkmalWert)
     * @param array<int, int> $available kMerkmalWert => number of products in the current result
     * @return T[]
     */
    public static function narrow(array $options, array $available): array
    {
        $result = [];
        foreach ($options as $option) {
            if ($option->isActive()) {
                // a value of this characteristic is selected – keep all alternatives
                $result[] = $option;
                continue;
            }
            $values = [];
            foreach ($option->getOptions() as $value) {
                $id = (int)$value->getID();
                if (!isset($available[$id])) {
                    continue;
                }
                $value->setCount($available[$id]);
                $values[] = $value;
            }
            if (\count($values) === 0) {
                continue;
            }
            if (\method_exists($option, 'setOptions')) {
                $option->setOptions($values);
            }
            if (\method_exists($option, 'setData')) {
                // backwards compatibility property of the core (oMerkmalWerte_arr)
                $option->setData('oMerkmalWerte_arr', $values);
            }
            $option->setCount(\count($values));
            $result[] = $option;
        }

        return $result;
    }

    /**
     * kMerkmalWert => product count over the current result with *all* active filters (same base query the core
     * uses for the characteristic filter, type "filter", so child products are treated identically).
     *
     * @return array<int, int>|null null on error (then nothing is hidden)
     */
    private function availableValues(ProductFilter $productFilter): ?array
    {
        try {
            $state = (new StateSQL())->from($productFilter->getCurrentStateData());
            $state->setOrderBy('');
            $state->setLimit('');
            $state->setGroupBy([]);
            $state->setSelect(['tartikel.kArtikel', 'fsam.kMerkmalWert']);
            // own alias: active characteristic filters already join a table named "tartikelmerkmal"
            $state->addJoin(
                (new Join())
                    ->setType('JOIN')
                    ->setTable('tartikelmerkmal AS fsam')
                    ->setOn('tartikel.kArtikel = fsam.kArtikel')
                    ->setOrigin(self::class)
            );
            $baseQuery = $productFilter->getFilterSQL()->getBaseQuery($state);
            $cacheID   = self::CACHE_PREFIX . \md5($baseQuery);
            $cached    = $this->cache?->get($cacheID);
            if (\is_array($cached)) {
                return $cached;
            }
            $available = [];
            foreach (
                $this->db->getObjects(
                    'SELECT x.kMerkmalWert, COUNT(DISTINCT x.kArtikel) AS cnt
                        FROM (' . $baseQuery . ') AS x
                        GROUP BY x.kMerkmalWert'
                ) as $row
            ) {
                $available[(int)$row->kMerkmalWert] = (int)$row->cnt;
            }
            $this->cache?->set($cacheID, $available, [\CACHING_GROUP_FILTER, \CACHING_GROUP_FILTER_CHARACTERISTIC]);

            return $available;
        } catch (\Throwable) {
            return null;
        }
    }
}
