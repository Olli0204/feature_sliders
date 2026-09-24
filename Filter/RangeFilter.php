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
 * Product filter for characteristics maintained as ranges in the Wawi ("35 - 55 kg").
 * URL value: "<from>_<to>". A product is shown when one of its values (or one of its
 * variation children's values) matches the selection according to the configured mode
 * (default: the ranges overlap).
 */
class RangeFilter extends AbstractFilter
{
    /** @var array<int, array<int, array{min: float|null, max: float|null}>> */
    private static array $rangeCache = [];

    /** @var array<string, string> */
    private static array $nameCache = [];

    private string $condition = '';

    private float $from = 0.0;

    private float $to = 0.0;

    /** @var array{min: float, max: float}|null */
    private ?array $bounds = null;

    private ?Settings $settings;

    public function __construct(ProductFilter $productFilter)
    {
        parent::__construct($productFilter);
        $this->settings = Settings::get();
        $this->setIsCustom(true)
            ->setType(Type::AND)
            ->setUrlParam($this->settings?->urlParam ?? 'mrf')
            ->setVisibility($this->settings?->isUsable() ? Visibility::SHOW_ALWAYS : Visibility::SHOW_NEVER)
            ->setFrontendName($this->getTitle())
            ->setFilterName($this->getFrontendName());
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
        if ($parsed === null || $this->settings === null || !$this->settings->isUsable()) {
            return $this;
        }
        [$this->from, $this->to] = $parsed;
        $this->value             = self::formatNumber($this->from) . '_' . self::formatNumber($this->to);
        $this->isInitialized     = true;
        $this->setName($this->getFrontendName() . ': ' . $this->formatRange($this->from, $this->to));

        $ids = [];
        foreach ($this->getValueRanges() as $valueID => $range) {
            if (RangeParser::matches($range, $this->from, $this->to, $this->settings->mode)) {
                $ids[] = $valueID;
            }
        }
        if (\count($ids) === 0) {
            $this->condition = '0 = 1';

            return $this;
        }
        $in              = \implode(',', $ids);
        $this->condition = '(EXISTS (SELECT 1 FROM tartikelmerkmal AS mrf_am
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
        if ($this->settings === null || !$this->settings->isUsable() || \count($this->getOptions()) === 0) {
            return null;
        }
        $min      = $this->bounds['min'];
        $max      = $this->bounds['max'];
        $active   = $this->isInitialized();
        $from     = $active ? \max($min, \min($this->from, $max)) : $min;
        $to       = $active ? \min($max, \max($this->to, $min)) : $max;
        $decimals = self::decimals($this->settings->step);

        return [
            'className' => $this->getClassName(),
            'id'        => 'mrf-' . $this->settings->characteristicID,
            'param'     => $this->getUrlParam(),
            'title'     => $this->getFrontendName(),
            'unit'      => $this->settings->unit,
            'min'       => \number_format($min, $decimals, '.', ''),
            'max'       => \number_format($max, $decimals, '.', ''),
            'from'      => \number_format($from, $decimals, '.', ''),
            'to'        => \number_format($to, $decimals, '.', ''),
            'step'      => self::formatNumber($this->settings->step),
            'active'    => $active,
            'baseUrl'   => $this->getBaseURL(),
            'tpl'       => $this->settings->frontendPath . 'tpl/slider.tpl'
        ];
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
     * Slider scale from all values of the characteristic that occur in the current
     * listing (all other active filters applied, this one ignored).
     *
     * @return array{min: float, max: float}|null
     */
    private function calculateBounds(): ?array
    {
        if ($this->settings === null || !$this->settings->isUsable()) {
            return null;
        }
        $ranges = $this->getValueRanges();
        if (\count($ranges) === 0) {
            return null;
        }
        $productFilter = $this->getProductFilter();
        $state         = (new StateSQL())->from($productFilter->getCurrentStateData($this->getClassName()));
        $state->setSelect(['tartikel.kArtikel']);
        $state->setOrderBy('');
        $state->setLimit('');
        $state->setGroupBy(['tartikel.kArtikel']);
        $baseQuery = $productFilter->getFilterSQL()->getBaseQuery($state);
        $cacheID   = 'mrf_bounds_' . $this->settings->characteristicID . '_' . \md5($baseQuery);
        $cache     = $productFilter->getCache();
        if (($valueIDs = $cache->get($cacheID)) === false) {
            $charID   = $this->settings->characteristicID;
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

        return RangeParser::bounds($present, $this->settings->step);
    }

    /**
     * @return array<int, array{min: float|null, max: float|null}> kMerkmalWert => parsed range
     */
    private function getValueRanges(): array
    {
        $charID = $this->settings?->characteristicID ?? 0;
        if ($charID <= 0) {
            return [];
        }
        if (!isset(self::$rangeCache[$charID])) {
            self::$rangeCache[$charID] = self::loadValueRanges($this->getProductFilter()->getDB(), $charID);
        }

        return self::$rangeCache[$charID];
    }

    /**
     * Parses every value of the characteristic; the shop's default language wins,
     * other languages are only used when it has no parseable text.
     *
     * @return array<int, array{min: float|null, max: float|null}>
     */
    public static function loadValueRanges(DbInterface $db, int $characteristicID): array
    {
        $rows   = $db->getObjects(
            "SELECT mw.kMerkmalWert, mws.cWert
                FROM tmerkmalwert AS mw
                JOIN tmerkmalwertsprache AS mws ON mws.kMerkmalWert = mw.kMerkmalWert
                LEFT JOIN tsprache AS sp ON sp.kSprache = mws.kSprache
                WHERE mw.kMerkmal = :cid
                ORDER BY mw.kMerkmalWert, (sp.cShopStandard = 'Y') DESC, mws.kSprache",
            ['cid' => $characteristicID]
        );
        $ranges = [];
        foreach ($rows as $row) {
            $valueID = (int)$row->kMerkmalWert;
            if (isset($ranges[$valueID])) {
                continue;
            }
            $range = RangeParser::parse((string)$row->cWert);
            if ($range !== null) {
                $ranges[$valueID] = $range;
            }
        }

        return $ranges;
    }

    private function getTitle(): string
    {
        if ($this->settings === null) {
            return '';
        }
        if ($this->settings->title !== '') {
            return $this->settings->title;
        }
        $charID = $this->settings->characteristicID;
        $langID = $this->getLanguageID();
        $key    = $charID . '_' . $langID;
        if (!isset(self::$nameCache[$key])) {
            $row                   = $charID > 0
                ? $this->getProductFilter()->getDB()->getSingleObject(
                    'SELECT COALESCE(ms.cName, m.cName) AS cName
                        FROM tmerkmal AS m
                        LEFT JOIN tmerkmalsprache AS ms ON ms.kMerkmal = m.kMerkmal AND ms.kSprache = :lid
                        WHERE m.kMerkmal = :cid',
                    ['cid' => $charID, 'lid' => $langID]
                )
                : null;
            self::$nameCache[$key] = \trim((string)($row->cName ?? ''));
        }

        return self::$nameCache[$key];
    }

    private function formatRange(float $from, float $to): string
    {
        $decimals = self::decimals($this->settings?->step ?? 1.0);
        $unit     = $this->settings?->unit ?? '';
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
