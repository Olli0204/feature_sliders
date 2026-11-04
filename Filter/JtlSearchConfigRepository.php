<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;

/**
 * Settings per JTL Search filter group (backend tab "JTL Search"). Groups are recorded by the frontend as soon
 * as JTL Search delivers them (record()), the admin then sets title, display, unit, step, order, visibility.
 *
 * Row: name, jtlTitle, jtlType (1 list, 2 slider, 3 colour, 4 tinybox), langs (ISO codes JTL Search delivered the
 * group in), active, title (default language), titles (ISO => title, other languages), display (auto|list|buttons),
 * unit, step (0 = JTL Search), expanded, sort, lastSeen (Y-m-d|null).
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
        $titles  = $row['titles'] ?? [];
        if (\is_string($titles)) {
            $titles = \json_decode($titles, true);
        }
        $cleanTitles = [];
        foreach (\is_array($titles) ? $titles : [] as $iso => $text) {
            $text = \mb_substr(\trim(\strip_tags((string)$text)), 0, 100);
            if (\preg_match('/^[a-z]{2,3}$/', (string)$iso) && $text !== '') {
                $cleanTitles[(string)$iso] = $text;
            }
        }
        $langs = $row['langs'] ?? '';
        $langs = \is_array($langs) ? $langs : \explode(',', (string)$langs);
        $step    = (float)\str_replace(',', '.', (string)($row['step'] ?? 0));

        return [
            'name'     => (string)($row['jtl_name'] ?? $row['name'] ?? ''),
            'jtlTitle' => (string)($row['jtl_title'] ?? ''),
            'jtlType'  => (int)($row['jtl_type'] ?? 1),
            'langs'    => \array_values(\array_filter(
                $langs,
                static fn($iso): bool => \is_string($iso) && \preg_match('/^[a-z]{2,3}$/', $iso) === 1
            )),
            'active'   => (bool)(int)($row['active'] ?? 1),
            'title'    => \mb_substr(\trim(\strip_tags((string)($row['title'] ?? ''))), 0, 100),
            'titles'   => $cleanTitles,
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
     * @param string                                         $language ISO code of the current shop language
     */
    public function record(array $groups, string $language = ''): void
    {
        $language = \preg_match('/^[a-z]{2,3}$/', $language) ? $language : '';
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
                        'INSERT IGNORE INTO ' . self::TABLE . ' (jtl_name, jtl_title, jtl_type, langs, sort, last_seen)
                            VALUES (:name, :title, :type, :langs, :sort, :seen)',
                        [
                            'name'  => $name,
                            'title' => \mb_substr($group['title'], 0, 255),
                            'type'  => $group['type'],
                            'langs' => $language,
                            'sort'  => ++$maxSort,
                            'seen'  => $today,
                        ]
                    );
                } catch (\Throwable) {
                    return;
                }
                $changed = true;
            } elseif (
                // JTL's title is remembered in the default language only (other languages: own title fields)
                ($existing['jtlTitle'] !== $group['title'] && $this->isDefaultLanguage($language))
                || $existing['jtlType'] !== $group['type']
                || $existing['lastSeen'] !== $today
                || ($language !== '' && !\in_array($language, $existing['langs'], true))
            ) {
                $langs = $existing['langs'];
                if ($language !== '' && !\in_array($language, $langs, true)) {
                    $langs[] = $language;
                }
                $this->db->queryPrepared(
                    'UPDATE ' . self::TABLE . ' SET jtl_title = :title, jtl_type = :type, langs = :langs,
                        last_seen = :seen WHERE jtl_name = :name',
                    [
                        'name'  => $name,
                        'title' => \mb_substr(
                            $this->isDefaultLanguage($language) ? $group['title'] : $existing['jtlTitle'],
                            0,
                            255
                        ),
                        'type'  => $group['type'],
                        'langs' => \implode(',', $langs),
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
                'UPDATE ' . self::TABLE . ' SET active = :active, title = :title, titles = :titles, display = :display,
                    unit = :unit, step = :step, expanded = :expanded, sort = :sort WHERE jtl_name = :name',
                [
                    'name'     => $row['name'],
                    'active'   => $row['active'] ? 1 : 0,
                    'title'    => $row['title'],
                    'titles'   => \count($row['titles']) > 0 ? \json_encode($row['titles'], \JSON_UNESCAPED_UNICODE) : null,
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

    /**
     * Backend dialog: saves the editable fields of one group (order stays).
     *
     * @param array<string, mixed> $row normalized row
     */
    public function saveOne(array $row): void
    {
        $this->db->queryPrepared(
            'UPDATE ' . self::TABLE . ' SET title = :title, titles = :titles, display = :display, unit = :unit,
                step = :step, expanded = :expanded WHERE jtl_name = :name',
            [
                'name'     => $row['name'],
                'title'    => $row['title'],
                'titles'   => \count($row['titles']) > 0 ? \json_encode($row['titles'], \JSON_UNESCAPED_UNICODE) : null,
                'display'  => $row['display'],
                'unit'     => $row['unit'],
                'step'     => $row['step'],
                'expanded' => $row['expanded'] ? 1 : 0,
            ]
        );
        $this->flush();
    }

    public function setActive(string $name, bool $active): void
    {
        $this->db->queryPrepared(
            'UPDATE ' . self::TABLE . ' SET active = :active WHERE jtl_name = :name',
            ['name' => $name, 'active' => $active ? 1 : 0]
        );
        $this->flush();
    }

    /**
     * @param string[] $names new order; groups not listed keep their place behind the listed ones
     */
    public function reorder(array $names): void
    {
        $known = $this->getAll();
        $order = \array_values(\array_unique(\array_filter($names, static fn($n): bool => isset($known[$n]))));
        foreach (\array_keys($known) as $name) {
            if (!\in_array($name, $order, true)) {
                $order[] = $name;
            }
        }
        foreach ($order as $i => $name) {
            $this->db->queryPrepared(
                'UPDATE ' . self::TABLE . ' SET sort = :sort WHERE jtl_name = :name',
                ['name' => $name, 'sort' => $i + 1]
            );
        }
        $this->flush();
    }

    /**
     * Title for the shop language: own title of that language, for the default language the main title field,
     * otherwise JTL Search's own (language-specific) title.
     *
     * @param array<string, mixed> $config
     */
    public static function titleFor(array $config, string $language, bool $isDefault, string $jtlTitle): string
    {
        if (($config['titles'][$language] ?? '') !== '') {
            return $config['titles'][$language];
        }
        if ($isDefault && $config['title'] !== '') {
            return $config['title'];
        }

        return $jtlTitle;
    }

    private ?string $defaultLanguage = null;

    public function setDefaultLanguage(string $iso): void
    {
        $this->defaultLanguage = $iso;
    }

    private function isDefaultLanguage(string $language): bool
    {
        return $language === '' || $this->defaultLanguage === null || $language === $this->defaultLanguage;
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
