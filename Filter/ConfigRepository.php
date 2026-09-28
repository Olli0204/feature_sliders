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
     * @return array<int, CharacteristicConfig> kMerkmal => config, only characteristics with a special display
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
     * @return array<int, CharacteristicConfig>
     */
    public function getSliders(): array
    {
        return \array_filter($this->getAll(), static fn(CharacteristicConfig $c): bool => $c->isSlider());
    }

    /**
     * Replaces the whole configuration. Characteristics set to "default" are removed.
     *
     * @param CharacteristicConfig[] $configs
     */
    public function saveAll(array $configs): void
    {
        $this->db->query('DELETE FROM ' . self::TABLE);
        foreach ($configs as $config) {
            if ($config->display === CharacteristicConfig::DISPLAY_DEFAULT) {
                continue;
            }
            $this->db->insert(self::TABLE, (object)[
                'kMerkmal' => $config->characteristicID,
                'display'  => $config->display,
                'mode'     => $config->mode,
                'unit'     => $config->unit,
                'step'     => $config->step,
                'expanded' => $config->expanded ? 1 : 0,
            ]);
        }
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
