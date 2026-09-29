<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\DB\DbInterface;
use JTL\Filter\Option;
use JTL\Filter\ProductFilter;

/**
 * Limits and orders the core characteristic filter of a category to its filter groups.
 *
 * The core already restricts the options by the Wawi category attribute "merkmalfilter" and by
 * "merkmalfilter_maxmerkmale" before plugins see them. Where either applies, the options are loaded
 * again without both, so the group assignment wins.
 */
final class GroupRestriction
{
    public function __construct(
        private readonly GroupRepository $groups,
        private readonly DbInterface $db
    ) {
    }

    /**
     * @return int[]|null allowed characteristic IDs in display order, null = no group assigned (core behaviour)
     */
    public function apply(ProductFilter $productFilter): ?array
    {
        if (!$productFilter->hasCategory()) {
            return null;
        }
        $categoryID = (int)$productFilter->getCategory()->getValue();
        $allowed    = $this->groups->getCharacteristicsForCategory($categoryID);
        if ($allowed === null) {
            return null;
        }
        $conf = $productFilter->getFilterConfig()->getConfig('navigationsfilter');
        if (($conf['merkmalfilter_verwenden'] ?? 'N') === 'N') {
            return $allowed;
        }
        $collection = $productFilter->getCharacteristicFilterCollection();
        if ((int)($conf['merkmalfilter_maxmerkmale'] ?? 0) > 0 || $this->hasWawiAttribute($categoryID)) {
            // bForce: no limits; no category: no Wawi attribute condition
            $collection->setOptions(null);
            $options = $collection->getOptions(['oAktuelleKategorie' => null, 'bForce' => true]);
        } else {
            $options = $productFilter->getSearchResults()->getCharacteristicFilterOptions();
        }
        $this->store($productFilter, self::filterAndSort($options, $allowed), (string)$conf['merkmalfilter_verwenden']);

        return $allowed;
    }

    /**
     * Re-sorts the current options (after slider placeholders were added).
     *
     * @param int[] $allowed
     */
    public function sort(ProductFilter $productFilter, array $allowed): void
    {
        $conf = $productFilter->getFilterConfig()->getConfig('navigationsfilter');
        $this->store(
            $productFilter,
            self::filterAndSort($productFilter->getSearchResults()->getCharacteristicFilterOptions(), $allowed),
            (string)($conf['merkmalfilter_verwenden'] ?? 'Y')
        );
    }

    /**
     * @param Option[] $options
     * @param int[]    $allowed
     * @return Option[]
     */
    public static function filterAndSort(array $options, array $allowed): array
    {
        $position = \array_flip($allowed);
        $options  = \array_values(\array_filter(
            $options,
            static fn($option): bool => isset($position[(int)$option->getValue()])
        ));
        \usort(
            $options,
            static fn($a, $b): int => $position[(int)$a->getValue()] <=> $position[(int)$b->getValue()]
        );

        return $options;
    }

    /**
     * @param Option[] $options
     */
    private function store(ProductFilter $productFilter, array $options, string $visibility): void
    {
        $collection = $productFilter->getCharacteristicFilterCollection();
        $productFilter->getSearchResults()->setCharacteristicFilterOptions($options);
        $collection->setOptions($options);
        $collection->setFilterCollection($options);
        if (\count($options) === 0) {
            $collection->hide();
        } elseif ($collection->isHidden()) {
            $collection->setVisibility($visibility);
        }
    }

    private function hasWawiAttribute(int $categoryID): bool
    {
        try {
            return $this->db->getSingleObject(
                "SELECT 1 AS found FROM tkategorieattribut
                    WHERE kKategorie = :cid AND cName = :name AND cWert <> ''
                    LIMIT 1",
                ['cid' => $categoryID, 'name' => \KAT_ATTRIBUT_MERKMALFILTER]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }
}
