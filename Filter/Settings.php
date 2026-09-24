<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Plugin\PluginInterface;

/**
 * Normalised plugin settings. Shared statically because the core creates
 * additional filter instances with `new $className($productFilter)`.
 */
final class Settings
{
    private static ?self $current = null;

    public function __construct(
        public readonly bool $active,
        public readonly int $characteristicID,
        public readonly string $mode,
        public readonly string $unit,
        public readonly float $step,
        public readonly string $title,
        public readonly bool $hideDefaultFilter,
        public readonly string $urlParam,
        public readonly string $pluginVersion = '',
        public readonly string $frontendPath = '',
        public readonly string $cacheGroup = ''
    ) {
    }

    public static function fromPlugin(PluginInterface $plugin): self
    {
        $config = $plugin->getConfig();
        $mode   = (string)$config->getValue('mrf_mode');
        $step   = (float)\str_replace(',', '.', (string)$config->getValue('mrf_step'));
        $param  = \strtolower((string)$config->getValue('mrf_url_param'));

        return new self(
            (string)$config->getValue('mrf_active') === 'on',
            (int)$config->getValue('mrf_characteristic'),
            \in_array($mode, [RangeParser::MODE_CONTAINS, RangeParser::MODE_WITHIN], true)
                ? $mode
                : RangeParser::MODE_OVERLAP,
            \trim((string)$config->getValue('mrf_unit')),
            $step > 0 ? $step : 1.0,
            \trim((string)$config->getValue('mrf_title')),
            (string)$config->getValue('mrf_hide_default') === 'on',
            \preg_match('/^[a-z][a-z0-9_]{1,19}$/', $param) ? $param : 'mrf',
            (string)$plugin->getMeta()->getVersion(),
            $plugin->getPaths()->getFrontendPath(),
            $plugin->getCache()->getGroup()
        );
    }

    public function isUsable(): bool
    {
        return $this->active && $this->characteristicID > 0;
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
