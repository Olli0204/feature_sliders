<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Plugin\PluginInterface;

/**
 * Global plugin settings (the per-characteristic ones live in CharacteristicConfig).
 * Shared statically because the core may create filter instances with `new $className($productFilter)`.
 */
final class Settings
{
    private static ?self $current = null;

    public function __construct(
        public readonly bool $active,
        public readonly string $urlParamPrefix = 'mrf',
        public readonly string $pluginVersion = '',
        public readonly string $frontendPath = '',
        public readonly string $cacheGroup = ''
    ) {
    }

    public static function fromPlugin(PluginInterface $plugin): self
    {
        $config = $plugin->getConfig();
        $prefix = \strtolower((string)$config->getValue('mrf_url_param'));

        return new self(
            (string)$config->getValue('mrf_active') === 'on',
            \preg_match('/^[a-z][a-z_]{1,15}$/', $prefix) ? $prefix : 'mrf',
            (string)$plugin->getMeta()->getVersion(),
            $plugin->getPaths()->getFrontendPath(),
            $plugin->getCache()->getGroup()
        );
    }

    /**
     * URL parameter of one characteristic, e.g. "mrf12".
     */
    public function urlParam(int $characteristicID): string
    {
        return $this->urlParamPrefix . $characteristicID;
    }

    public static function set(self $settings): void
    {
        self::$current = $settings;
    }

    public static function get(): ?self
    {
        return self::$current;
    }
}
