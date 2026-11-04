<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;

/**
 * Settings per JTL Search filter group (backend tab "JTL Search"). Groups are recorded by the frontend as soon
 * as JTL Search delivers them (record()), the admin then sets title, display, unit, step, order, visibility.
 *
 * Row: name, jtlTitle, jtlType (1 list, 2 slider, 3 colour, 4 tinybox), active, title, display
 * (auto|list|buttons), unit, step (0 = JTL Search), expanded, sort, lastSeen (Y-m-d|null).
 */
final class JtlSearchConfigRepository
{
    public const TABLE = 'feature_sliders_jtlsearch';

    public const DISPLAY_AUTO    = 'auto';
    public const DISPLAY_LIST    = 'list';
    public const DISPLAY_BUTTONS = 'buttons';
    public const DISPLAYS        = [self::DISPLAY_AUTO, self::DISPLAY_LIST, self::DISPLAY_BUTTONS];

    public const TYPE_SLIDER = 2;

    private const CACHE_ID = 'feature_sliders_jtlsearch';

    public function __construct(
        private readonly DbInterface $db,
        private readonly ?JTLCacheInterface $cache = null,
        private readonly string $cacheGroup = ''
    ) {
    }

    /**
     * @return array<string, array<string, mixed>> keyed by JTL Search field name, in configured order
     */
    public function getAll(): array
    {
        $rows = $this->cache?->get(self::CACHE_ID);
        if (!\is_array($rows)) {
            try {
                $rows = \array_map(
                    static fn($row): array => (array)$row,
                    $this->db->getObjects('SELECT * FROM ' . self::TABLE . ' ORDER BY sort, jtl_name')
                );
            } catch (\Throwable) {
                // table missing (migration not run yet) – nothing configured
                return [];
            }
            $this->cache?->set(self::CACHE_ID, $rows, \array_filter([$this->cacheGroup]));
        }
        $result = [];
        foreach ($rows as $row) {
            $config                  = self::normalize($row);
            $result[$config['name']] = $config;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row DB row or form data
     * @return array<string, mixed>
     */
    public static function normalize(array $row): array
    {
        $display = (string)($row['display'] ?? self::DISPLAY_AUTO);
        $step    = (float)\str_replace(',', '.', (string)($row['step'] ?? 0));

        return [
            'name'     => (string)($row['jtl_name'] ?? $row['name'] ?? ''),
            'jtlTitle' => (string)($row['jtl_title'] ?? ''),
            'jtlType'  => (int)($row['jtl_type'] ?? 1),
            'active'   => (bool)(int)($row['active'] ?? 1),
            'title'    => \mb_substr(\trim(\strip_tags((string)($row['title'] ?? ''))), 0, 100),
            'display'  => \in_array($display, self::DISPLAYS, true) ? $display : self::DISPLAY_AUTO,
            'unit'     => \mb_substr(\trim(\strip_tags((string)($row['unit'] ?? ''))), 0, 20),
            'step'     => $step > 0 && $step < 1000000 ? \round($step, 3) : 0.0,
            'expanded' => (bool)(int)($row['expanded'] ?? 0),
            'sort'     => (int)($row['sort'] ?? 0),
            'lastSeen' => isset($row['last_seen']) && $row['last_seen'] !== '' ? (string)$row['last_seen'] : null,
        ];
    }

    /**
     * Frontend: remember the groups JTL Search delivered. Writes only for new groups, a changed JTL title/type
     * or once a day (last seen) – a normal search request does not touch the database.
     *
     * @param array<string, array{title: string, type: int}> $groups field name => JTL title/type
     */
    public function record(array $groups): void
    {
        if (\count($groups) === 0) {
            return;
        }
        $known   = $this->getAll();
        $today   = \date('Y-m-d');
        $maxSort = \count($known) > 0 ? \max(\array_column($known, 'sort')) : 0;
        $changed = false;
        foreach ($groups as $name => $group) {
            $existing = $known[$name] ?? null;
            if ($existing === null) {
                try {
                    $this->db->queryPrepared(
                        'INSERT IGNORE INTO ' . self::TABLE . ' (jtl_name, jtl_title, jtl_type, sort, last_seen)
                            VALUES (:name, :title, :type, :sort, :seen)',
                        [
                            'name'  => $name,
                            'title' => \mb_substr($group['title'], 0, 255),
                            'type'  => $group['type'],
                            'sort'  => ++$maxSort,
                            'seen'  => $today,
                        ]
                    );
                } catch (\Throwable) {
                    return;
                }
                $changed = true;
            } elseif (
                $existing['jtlTitle'] !== $group['title']
                || $existing['jtlType'] !== $group['type']
                || $existing['lastSeen'] !== $today
            ) {
                $this->db->queryPrepared(
                    'UPDATE ' . self::TABLE . ' SET jtl_title = :title, jtl_type = :type, last_seen = :seen
                        WHERE jtl_name = :name',
                    [
                        'name'  => $name,
                        'title' => \mb_substr($group['title'], 0, 255),
                        'type'  => $group['type'],
                        'seen'  => $today,
                    ]
                );
                $changed = true;
            }
        }
        if ($changed) {
            $this->flush();
        }
    }

    /**
     * Backend: saves the editable fields of all rows; the order of $rows is the new sort order.
     *
     * @param array<int, array<string, mixed>> $rows normalized rows
     */
    public function saveAll(array $rows): void
    {
        foreach (\array_values($rows) as $i => $row) {
            $this->db->queryPrepared(
                'UPDATE ' . self::TABLE . ' SET active = :active, title = :title, display = :display, unit = :unit,
                    step = :step, expanded = :expanded, sort = :sort WHERE jtl_name = :name',
                [
                    'name'     => $row['name'],
                    'active'   => $row['active'] ? 1 : 0,
                    'title'    => $row['title'],
                    'display'  => $row['display'],
                    'unit'     => $row['unit'],
                    'step'     => $row['step'],
                    'expanded' => $row['expanded'] ? 1 : 0,
                    'sort'     => $i + 1,
                ]
            );
        }
        $this->flush();
    }

    public function remove(string $name): void
    {
        $this->db->queryPrepared('DELETE FROM ' . self::TABLE . ' WHERE jtl_name = :name', ['name' => $name]);
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
