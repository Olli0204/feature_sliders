<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\DB\DbInterface;
use JTL\Filter\AbstractFilter;
use JTL\Filter\FilterInterface;
use JTL\Filter\Option;
use JTL\Filter\ProductFilter;
use JTL\Filter\StateSQL;
use JTL\Filter\Type;
use JTL\Filter\Visibility;

/**
 * Slider filter for one characteristic. One instance is registered per configured characteristic,
 * each with its own URL parameter ("mrf<kMerkmal>=<from>_<to>").
 *
 * - slider_range:  values are ranges ("35 - 55 kg"), matched by the configured mode (default: overlap)
 * - slider_single: values are single numbers ("154 cm"), shown when the value lies inside the selection
 *
 * Values of variation children count for their parent.
 */
class RangeFilter extends AbstractFilter
{
    /** @var array<string, array{ranges: array<int, array{min: float|null, max: float|null}>, unit: string}> */
    private static array $valueCache = [];

    /** @var array<string, string> */
    private static array $nameCache = [];

    private string $condition = '';

    private float $from = 0.0;

    private float $to = 0.0;

    /** @var array{min: float, max: float}|null */
    private ?array $bounds = null;

    private ?Settings $settings;

    public function __construct(ProductFilter $productFilter, private readonly ?CharacteristicConfig $config = null)
    {
        parent::__construct($productFilter);
        $this->settings = Settings::get();
        $this->setIsCustom(true)
            ->setType(Type::AND)
            ->setUrlParam($this->isUsable() ? $this->settings->urlParam($this->config->characteristicID) : '')
            // never rendered as a filter of its own: the slider replaces the values of the
            // characteristic inside the core characteristic filter (see Bootstrap::prepareListing())
            ->setVisibility(Visibility::SHOW_NEVER)
            ->setFrontendName($this->getTitle())
            ->setFilterName($this->getFrontendName());
    }

    public function isUsable(): bool
    {
        return $this->settings !== null
            && $this->settings->active
            && $this->config !== null
            && $this->config->isSlider()
            && $this->config->characteristicID > 0;
    }

    public function getCharacteristicConfig(): ?CharacteristicConfig
    {
        return $this->config;
    }

    public function getCharacteristicID(): int
    {
        return $this->config?->characteristicID ?? 0;
    }

    /**
     * @inheritdoc
     */
    public function setSeo(array $languages): FilterInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function init($value): FilterInterface
    {
        $this->condition     = '';
        $this->isInitialized = false;
        $this->value         = null;
        $parsed              = self::parseValue($value);
        if ($parsed === null || !$this->isUsable()) {
            return $this;
        }
        [$this->from, $this->to] = $parsed;
        $this->value             = self::formatNumber($this->from) . '_' . self::formatNumber($this->to);
        $this->isInitialized     = true;
        $this->setName($this->getFrontendName() . ': ' . $this->formatRange($this->from, $this->to));

        $ids  = [];
        $mode = $this->config->effectiveMode();
        foreach ($this->getValueData()['ranges'] as $valueID => $range) {
            if (RangeParser::matches($range, $this->from, $this->to, $mode)) {
                $ids[] = $valueID;
            }
        }
        // the tag keeps the condition unique, so calculateBounds() can remove exactly this one
        $tag = '/* ' . $this->getUrlParam() . ' */ ';
        if (\count($ids) === 0) {
            $this->condition = $tag . '0 = 1';

            return $this;
        }
        $in              = \implode(',', $ids);
        $this->condition = $tag . '(EXISTS (SELECT 1 FROM tartikelmerkmal AS mrf_am
                    WHERE mrf_am.kArtikel = tartikel.kArtikel AND mrf_am.kMerkmalWert IN (' . $in . '))
                OR EXISTS (SELECT 1 FROM tartikel AS mrf_child
                    JOIN tartikelmerkmal AS mrf_cm ON mrf_cm.kArtikel = mrf_child.kArtikel
                    WHERE mrf_child.kVaterArtikel = tartikel.kArtikel AND mrf_cm.kMerkmalWert IN (' . $in . ')))';

        return $this;
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    public static function parseValue(mixed $value): ?array
    {
        if (\is_array($value)) {
            $value = \reset($value);
        }
        if (!\is_string($value) && !\is_numeric($value)) {
            return null;
        }
        if (!\preg_match('/^(\d{1,6}(?:\.\d{1,3})?)_(\d{1,6}(?:\.\d{1,3})?)$/', \trim((string)$value), $hits)) {
            return null;
        }
        $from = (float)$hits[1];
        $to   = (float)$hits[2];

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    public function getSQLCondition(): string
    {
        return $this->condition;
    }

    /**
     * @inheritdoc
     */
    public function getSQLJoin(): array
    {
        return [];
    }

    /**
     * One option describing the slider scale; an empty result hides the filter.
     *
     * @inheritdoc
     */
    public function getOptions($mixed = null): array
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $this->options = [];
        $this->bounds  = $this->calculateBounds();
        if ($this->bounds === null) {
            return $this->options;
        }
        $option = new Option();
        $option->setParam($this->getUrlParam())
            ->setType($this->getType())
            ->setClassName($this->getClassName())
            ->setName($this->formatRange($this->bounds['min'], $this->bounds['max']))
            ->setValue(self::formatNumber($this->bounds['min']) . '_' . self::formatNumber($this->bounds['max']))
            ->setCount(1)
            ->setSort(0);
        $option->setURL($this->getBaseURL());
        $option->setIsActive($this->isInitialized());
        $this->options = [$option];

        return $this->options;
    }

    /**
     * Everything the slider templates need, or null when there is nothing to show.
     *
     * @return array<string, mixed>|null
     */
    public function getSliderData(): ?array
    {
        if (!$this->isUsable() || \count($this->getOptions()) === 0) {
            return null;
        }
        $min      = $this->bounds['min'];
        $max      = $this->bounds['max'];
        $active   = $this->isInitialized();
        $from     = $active ? \max($min, \min($this->from, $max)) : $min;
        $to       = $active ? \min($max, \max($this->to, $min)) : $max;
        $decimals = self::decimals($this->config->step);

        return [
            'id'               => 'mrf-' . $this->config->characteristicID,
            'characteristicID' => $this->config->characteristicID,
            'display'          => $this->config->display,
            'expanded'         => $this->config->expanded,
            'param'            => $this->getUrlParam(),
            'title'            => $this->getFrontendName(),
            'unit'             => $this->getUnit(),
            'min'              => \number_format($min, $decimals, '.', ''),
            'max'              => \number_format($max, $decimals, '.', ''),
            'from'             => \number_format($from, $decimals, '.', ''),
            'to'               => \number_format($to, $decimals, '.', ''),
            'step'             => self::formatNumber($this->config->step),
            'active'           => $active,
            'baseUrl'          => $this->getBaseURL(),
            'tpl'              => $this->settings->frontendPath . 'tpl/slider.tpl'
        ];
    }

    /**
     * Configured unit, or the one written behind the values in the Wawi.
     */
    public function getUnit(): string
    {
        if ($this->config === null) {
            return '';
        }

        return $this->config->unit !== '' ? $this->config->unit : $this->getValueData()['unit'];
    }

    /**
     * URL of the current listing without this filter; the frontend appends "param=from_to".
     */
    private function getBaseURL(): string
    {
        $unset = clone $this;
        $unset->setDoUnset(true);

        return $this->getProductFilter()->getFilterURL()->getURL($unset);
    }

    /**
     * Slider scale from all values of the characteristic that occur in the current listing
     * (all other active filters applied – including other sliders – this one removed).
     *
     * @return array{min: float, max: float}|null
     */
    private function calculateBounds(): ?array
    {
        if (!$this->isUsable()) {
            return null;
        }
        $ranges = $this->getValueData()['ranges'];
        if (\count($ranges) === 0) {
            return null;
        }
        $productFilter = $this->getProductFilter();
        $state         = (new StateSQL())->from($productFilter->getCurrentStateData());
        if ($this->condition !== '') {
            $own = \trim($this->condition);
            $state->setConditions(\array_values(\array_filter(
                $state->getConditions(),
                static fn($condition): bool => !\is_string($condition) || \trim($condition) !== $own
            )));
        }
        $state->setSelect(['tartikel.kArtikel']);
        $state->setOrderBy('');
        $state->setLimit('');
        $state->setGroupBy(['tartikel.kArtikel']);
        $baseQuery = $productFilter->getFilterSQL()->getBaseQuery($state);
        $charID    = $this->config->characteristicID;
        $cacheID   = 'mrf_bounds_' . $charID . '_' . \md5($baseQuery);
        $cache     = $productFilter->getCache();
        if (($valueIDs = $cache->get($cacheID)) === false) {
            $valueIDs = \array_map(
                static fn($row): int => (int)$row->kMerkmalWert,
                $productFilter->getDB()->getObjects(
                    'SELECT mrf_am.kMerkmalWert
                        FROM (' . $baseQuery . ') AS mrf_base
                        JOIN tartikelmerkmal AS mrf_am
                            ON mrf_am.kArtikel = mrf_base.kArtikel AND mrf_am.kMerkmal = :cid
                    UNION
                    SELECT mrf_cm.kMerkmalWert
                        FROM (' . $baseQuery . ') AS mrf_base2
                        JOIN tartikel AS mrf_child ON mrf_child.kVaterArtikel = mrf_base2.kArtikel
                        JOIN tartikelmerkmal AS mrf_cm
                            ON mrf_cm.kArtikel = mrf_child.kArtikel AND mrf_cm.kMerkmal = :cid2',
                    ['cid' => $charID, 'cid2' => $charID]
                )
            );
            $cache->set(
                $cacheID,
                $valueIDs,
                \array_filter([\CACHING_GROUP_FILTER, \CACHING_GROUP_FILTER_CHARACTERISTIC, $this->settings->cacheGroup])
            );
        }
        $present = \array_intersect_key($ranges, \array_flip($valueIDs));

        return RangeParser::bounds($present, $this->config->step);
    }

    /**
     * @return array{ranges: array<int, array{min: float|null, max: float|null}>, unit: string}
     */
    private function getValueData(): array
    {
        if ($this->config === null) {
            return ['ranges' => [], 'unit' => ''];
        }
        $key = $this->config->characteristicID . '_' . $this->config->display;
        if (!isset(self::$valueCache[$key])) {
            self::$valueCache[$key] = self::loadValueData(
                $this->getProductFilter()->getDB(),
                $this->config->characteristicID,
                $this->config->isSingleValue()
            );
        }

        return self::$valueCache[$key];
    }

    /**
     * Parses every value of the characteristic; the shop's default language wins,
     * other languages are only used when it has no parseable text.
     *
     * @return array{
     *     ranges: array<int, array{min: float|null, max: float|null}>,
     *     unit: string,
     *     values: array<int, array{text: string, range: array{min: float|null, max: float|null}|null}>
     * }
     */
    public static function loadValueData(DbInterface $db, int $characteristicID, bool $singleValue): array
    {
        $rows   = $db->getObjects(
            "SELECT mw.kMerkmalWert, mws.cWert
                FROM tmerkmalwert AS mw
                JOIN tmerkmalwertsprache AS mws ON mws.kMerkmalWert = mw.kMerkmalWert
                LEFT JOIN tsprache AS sp ON sp.kSprache = mws.kSprache
                WHERE mw.kMerkmal = :cid
                ORDER BY mw.nSort, mw.kMerkmalWert, (sp.cShopStandard = 'Y') DESC, mws.kSprache",
            ['cid' => $characteristicID]
        );
        $values = [];
        foreach ($rows as $row) {
            $valueID = (int)$row->kMerkmalWert;
            $text    = (string)$row->cWert;
            $range   = $singleValue ? RangeParser::parseSingle($text) : RangeParser::parse($text);
            if (!isset($values[$valueID]) || ($values[$valueID]['range'] === null && $range !== null)) {
                $values[$valueID] = ['text' => $text, 'range' => $range];
            }
        }
        $ranges = \array_filter(\array_map(static fn(array $v): ?array => $v['range'], $values));

        return [
            'ranges' => $ranges,
            'unit'   => RangeParser::detectUnit(\array_column($values, 'text')),
            'values' => $values,
        ];
    }

    private function getTitle(): string
    {
        $charID = $this->getCharacteristicID();
        if ($charID <= 0) {
            return '';
        }
        $langID = $this->getLanguageID();
        $key    = $charID . '_' . $langID;
        if (!isset(self::$nameCache[$key])) {
            $row                   = $this->getProductFilter()->getDB()->getSingleObject(
                'SELECT COALESCE(ms.cName, m.cName) AS cName
                    FROM tmerkmal AS m
                    LEFT JOIN tmerkmalsprache AS ms ON ms.kMerkmal = m.kMerkmal AND ms.kSprache = :lid
                    WHERE m.kMerkmal = :cid',
                ['cid' => $charID, 'lid' => $langID]
            );
            self::$nameCache[$key] = \trim((string)($row->cName ?? ''));
        }

        return self::$nameCache[$key];
    }

    private function formatRange(float $from, float $to): string
    {
        $decimals = self::decimals($this->config?->step ?? 1.0);
        $unit     = $this->getUnit();
        $text     = \number_format($from, $decimals, ',', '.') . ' – ' . \number_format($to, $decimals, ',', '.');

        return $unit !== '' ? $text . ' ' . $unit : $text;
    }

    public static function formatNumber(float $number): string
    {
        return \rtrim(\rtrim(\number_format($number, 3, '.', ''), '0'), '.');
    }

    private static function decimals(float $step): int
    {
        $fraction = \explode('.', self::formatNumber($step))[1] ?? '';

        return \strlen($fraction);
    }
}
