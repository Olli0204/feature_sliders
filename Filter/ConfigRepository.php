<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;

/**
 * Reads and writes the per-characteristic display configuration.
 */
final class ConfigRepository
{
    public const TABLE = 'feature_sliders_characteristic';

    private const CACHE_ID = 'feature_sliders_characteristics';

    public function __construct(
        private readonly DbInterface $db,
        private readonly ?JTLCacheInterface $cache = null,
        private readonly string $cacheGroup = ''
    ) {
    }

    /**
     * @return array<int, CharacteristicConfig> kMerkmal => config, only characteristics with a special
     *         display – active and inactive
     */
    public function getAll(): array
    {
        $rows = $this->cache?->get(self::CACHE_ID);
        if (!\is_array($rows)) {
            try {
                $rows = \array_map(
                    static fn($row): array => (array)$row,
                    $this->db->getObjects('SELECT * FROM ' . self::TABLE . ' ORDER BY kMerkmal')
                );
            } catch (\Throwable) {
                // table missing (migration not run yet) – behave as if nothing is configured
                return [];
            }
            $this->cache?->set(self::CACHE_ID, $rows, \array_filter([$this->cacheGroup]));
        }
        $configs = [];
        foreach ($rows as $row) {
            $config = CharacteristicConfig::fromArray((int)$row['kMerkmal'], $row);
            if ($config->display !== CharacteristicConfig::DISPLAY_DEFAULT) {
                $configs[$config->characteristicID] = $config;
            }
        }

        return $configs;
    }

    /**
     * Active slider characteristics (inactive ones keep their settings but show the core filter).
     *
     * @return array<int, CharacteristicConfig>
     */
    public function getSliders(): array
    {
        return \array_filter(
            $this->getAll(),
            static fn(CharacteristicConfig $c): bool => $c->active && $c->isSlider()
        );
    }

    /**
     * Active button-box characteristics.
     *
     * @return array<int, CharacteristicConfig>
     */
    public function getButtons(): array
    {
        return \array_filter(
            $this->getAll(),
            static fn(CharacteristicConfig $c): bool => $c->active && $c->isButtons()
        );
    }

    public function get(int $characteristicID): ?CharacteristicConfig
    {
        return $this->getAll()[$characteristicID] ?? null;
    }

    /**
     * Inserts or replaces one characteristic; "default" removes it.
     */
    public function save(CharacteristicConfig $config): void
    {
        $this->db->queryPrepared(
            'DELETE FROM ' . self::TABLE . ' WHERE kMerkmal = :id',
            ['id' => $config->characteristicID]
        );
        if ($config->display !== CharacteristicConfig::DISPLAY_DEFAULT) {
            $this->db->insert(self::TABLE, (object)[
                'kMerkmal'       => $config->characteristicID,
                'active'         => $config->active ? 1 : 0,
                'display'        => $config->display,
                'mode'           => $config->mode,
                'unit'           => $config->unit,
                'step'           => $config->step,
                'expanded'       => $config->expanded ? 1 : 0,
                'button_columns' => $config->buttonColumns,
            ]);
        }
        $this->flush();
    }

    public function setActive(int $characteristicID, bool $active): void
    {
        $this->db->queryPrepared(
            'UPDATE ' . self::TABLE . ' SET active = :active WHERE kMerkmal = :id',
            ['active' => $active ? 1 : 0, 'id' => $characteristicID]
        );
        $this->flush();
    }

    public function remove(int $characteristicID): void
    {
        $this->db->queryPrepared(
            'DELETE FROM ' . self::TABLE . ' WHERE kMerkmal = :id',
            ['id' => $characteristicID]
        );
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
