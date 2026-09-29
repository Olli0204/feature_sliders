<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;

/**
 * Filter groups: an ordered list of characteristics assigned to categories. A category with at least
 * one group shows only the characteristics of its groups, in group order, then position within the group.
 * No inheritance – a group applies to exactly the assigned categories.
 */
final class GroupRepository
{
    public const TABLE_GROUP          = 'feature_sliders_group';
    public const TABLE_CHARACTERISTIC = 'feature_sliders_group_characteristic';
    public const TABLE_CATEGORY       = 'feature_sliders_group_category';

    private const CACHE_ID = 'feature_sliders_groups';

    public function __construct(
        private readonly DbInterface $db,
        private readonly ?JTLCacheInterface $cache = null,
        private readonly string $cacheGroup = ''
    ) {
    }

    /**
     * @return array<int, array{id: int, name: string, sort: int, characteristics: int[], categories: int[]}>
     *         in display order, keyed by id
     */
    public function getAll(): array
    {
        $groups = $this->cache?->get(self::CACHE_ID);
        if (\is_array($groups)) {
            return $groups;
        }
        try {
            $rows = $this->db->getObjects(
                'SELECT id, name, sort FROM ' . self::TABLE_GROUP . ' ORDER BY sort, id'
            );
            $groups = [];
            foreach ($rows as $row) {
                $groups[(int)$row->id] = [
                    'id'              => (int)$row->id,
                    'name'            => (string)$row->name,
                    'sort'            => (int)$row->sort,
                    'characteristics' => [],
                    'categories'      => [],
                ];
            }
            foreach (
                $this->db->getObjects(
                    'SELECT group_id, kMerkmal FROM ' . self::TABLE_CHARACTERISTIC . ' ORDER BY group_id, sort, kMerkmal'
                ) as $row
            ) {
                if (isset($groups[(int)$row->group_id])) {
                    $groups[(int)$row->group_id]['characteristics'][] = (int)$row->kMerkmal;
                }
            }
            foreach (
                $this->db->getObjects(
                    'SELECT group_id, kKategorie FROM ' . self::TABLE_CATEGORY . ' ORDER BY group_id, kKategorie'
                ) as $row
            ) {
                if (isset($groups[(int)$row->group_id])) {
                    $groups[(int)$row->group_id]['categories'][] = (int)$row->kKategorie;
                }
            }
        } catch (\Throwable) {
            // tables missing (migration not run yet) – behave as if no groups exist
            return [];
        }
        $this->cache?->set(self::CACHE_ID, $groups, \array_filter([$this->cacheGroup]));

        return $groups;
    }

    public function get(int $id): ?array
    {
        return $this->getAll()[$id] ?? null;
    }

    /**
     * Ordered characteristic IDs allowed in a category, or null when no group is assigned
     * (then the shop behaves as before: Wawi attribute "merkmalfilter" or all characteristics).
     *
     * @return int[]|null
     */
    public function getCharacteristicsForCategory(int $categoryID): ?array
    {
        if ($categoryID <= 0) {
            return null;
        }
        $assigned = false;
        $ordered  = [];
        foreach ($this->getAll() as $group) {
            if (!\in_array($categoryID, $group['categories'], true)) {
                continue;
            }
            $assigned = true;
            foreach ($group['characteristics'] as $characteristicID) {
                if (!\in_array($characteristicID, $ordered, true)) {
                    $ordered[] = $characteristicID;
                }
            }
        }

        return $assigned ? $ordered : null;
    }

    /**
     * Creates (id 0) or replaces a group. Returns the group id.
     *
     * @param int[] $characteristics ordered
     * @param int[] $categories
     */
    public function save(int $id, string $name, array $characteristics, array $categories): int
    {
        $name = \mb_substr(\trim(\strip_tags($name)), 0, 100);
        if ($id > 0 && $this->get($id) !== null) {
            $this->db->queryPrepared(
                'UPDATE ' . self::TABLE_GROUP . ' SET name = :name WHERE id = :id',
                ['name' => $name, 'id' => $id]
            );
        } else {
            $sort = (int)($this->db->getSingleObject(
                'SELECT COALESCE(MAX(sort), 0) + 1 AS next FROM ' . self::TABLE_GROUP
            )->next ?? 1);
            $id   = $this->db->insert(self::TABLE_GROUP, (object)['name' => $name, 'sort' => $sort]);
        }
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE_CHARACTERISTIC . ' WHERE group_id = :id', ['id' => $id]);
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE_CATEGORY . ' WHERE group_id = :id', ['id' => $id]);
        $position = 0;
        foreach (\array_values(\array_unique(\array_filter(\array_map('\intval', $characteristics)))) as $cid) {
            $this->db->insert(
                self::TABLE_CHARACTERISTIC,
                (object)['group_id' => $id, 'kMerkmal' => $cid, 'sort' => ++$position]
            );
        }
        foreach (\array_unique(\array_filter(\array_map('\intval', $categories))) as $categoryID) {
            $this->db->insert(self::TABLE_CATEGORY, (object)['group_id' => $id, 'kKategorie' => $categoryID]);
        }
        $this->flush();

        return $id;
    }

    /**
     * @param int[] $ids group ids in the new order
     */
    public function reorder(array $ids): void
    {
        $position = 0;
        foreach (\array_values(\array_unique(\array_map('\intval', $ids))) as $id) {
            $this->db->queryPrepared(
                'UPDATE ' . self::TABLE_GROUP . ' SET sort = :sort WHERE id = :id',
                ['sort' => ++$position, 'id' => $id]
            );
        }
        $this->flush();
    }

    public function remove(int $id): void
    {
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE_CATEGORY . ' WHERE group_id = :id', ['id' => $id]);
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE_CHARACTERISTIC . ' WHERE group_id = :id', ['id' => $id]);
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE_GROUP . ' WHERE id = :id', ['id' => $id]);
        $this->flush();
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
