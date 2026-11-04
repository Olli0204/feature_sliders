<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Filter;

use JTL\Filter\CharacteristicOption;
use JTL\Filter\Option;
use JTL\Filter\ProductFilter;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use stdClass;

/**
 * Search page with the JTL Search plugin (jtl_search): its server returns the result page and the filter groups
 * itself, the core switches all shop filters off (ProductFilter::isExtendedJTLSearch()). JTL's own box
 * "Filter (JTL Search)" uses old panel markup, is desktop-only and NOVA's paging/sorting links drop its filters.
 *
 * This class turns the JTL Search filter groups into core CharacteristicOption objects, so NOVA renders them
 * with its own characteristic filter (sidebar box and mobile filter modal), and appends the active filters
 * (URL parameters fq0, fq1, …) to the paging, sorting and items-per-page links.
 *
 * URL format as in jtl_search 1.24.0 (JtlSearch::extendFilterItemURL(), frontend/boxes/box_filter.tpl):
 *   /?suchausdruck=<query>&fq0=<group>:<value>&fq1=<group>:<from>_<to>-<lo>_<hi>
 * Values are sent ISO-8859-1 encoded like JTL does; the plugin reads fq0…fq19.
 */
final class JtlSearchFilters
{
    /** synthetic characteristic IDs – far above real kMerkmal values, used for DOM ids and template lookups */
    public const ID_OFFSET = 990000;

    private const TYPE_SLIDER  = 2;
    private const TYPE_TINYBOX = 4;
    private const MAX_FILTERS  = 20;

    private const GROUP_PATTERN = '[A-Za-z0-9_]+';

    /** @var array<int, array{key: string, enc: string}> active filters from the request, in URL order */
    private array $entries = [];

    private string $baseUrl = '';

    private bool $prepared = false;

    /**
     * @param array<string, array<string, mixed>> $configs backend tab "JTL Search", keyed by field name
     *                                                     (JtlSearchConfigRepository::getAll())
     */
    public function __construct(
        private readonly string $frontendPath,
        private readonly array $configs = [],
        private readonly ?JtlSearchConfigRepository $repository = null
    ) {
    }

    /**
     * HOOK_FILTER_PAGE (before the boxes are rendered). Returns false when there is nothing to show –
     * JTL's own box then stays untouched as fallback.
     *
     * @param array{from: string, to: string} $labels
     * @return array{sliders: array<int, array<string, mixed>>, buttons: array<int, array<string, mixed>>}|null
     */
    public function prepare(ProductFilter $productFilter, JTLSmarty $smarty, array $labels): ?array
    {
        $response = $smarty->getTemplateVars('oExtendedJTLSearchResponse');
        $groups   = $response->oSearch->oFilterGroup_arr ?? null;
        if (!$response instanceof stdClass || !\is_array($groups)) {
            return null;
        }
        $useFilter = (string)($productFilter->getFilterConfig()->getConfig('navigationsfilter')
            ['merkmalfilter_verwenden'] ?? 'N');
        if ($useFilter === 'N') {
            // the characteristic filter is switched off shop-wide – nothing could render our options
            return null;
        }
        $this->baseUrl = Shop::getURL() . '/?suchausdruck=' . \urlencode($this->query($productFilter, $response));
        $this->entries = self::requestEntries($_GET);

        $options = [];
        $order   = [];
        $sliders = [];
        $buttons = [];
        $active  = [];
        $seen    = [];
        foreach (\array_values($groups) as $index => $group) {
            if (!$group instanceof stdClass || !\is_array($group->oFilterItem_arr ?? null)) {
                continue;
            }
            $id    = self::ID_OFFSET + $index;
            $name  = (string)($group->cName ?? '');
            $type  = (int)($group->nType ?? 1);
            $title = self::plain((string)($group->cMapping ?? $name));
            if (!\preg_match('/^' . self::GROUP_PATTERN . '$/', $name) || \count($group->oFilterItem_arr) === 0) {
                continue;
            }
            $seen[$name] = ['title' => $title, 'type' => $type];
            $config      = $this->configs[$name] ?? null;
            if ($config !== null && !$config['active']) {
                continue;
            }
            if ($config !== null && $config['title'] !== '') {
                $title = $config['title'];
            }
            $option = $this->createGroup($id, $title);
            if ($type === self::TYPE_SLIDER) {
                $slider = $this->sliderData($group, $name, $labels, $config);
                if ($slider === null) {
                    continue;
                }
                $option->setIsActive($slider['active']);
                if ($slider['active']) {
                    $active[] = [
                        'label' => $title . ': ' . $slider['from'] . ' – ' . $slider['to']
                            . ($slider['unit'] !== '' ? ' ' . $slider['unit'] : ''),
                        'url'   => $this->buildUrl($this->withoutGroup($name)),
                    ];
                }
                $sliders[$id] = $slider;
            } else {
                $values = [];
                foreach ($group->oFilterItem_arr as $item) {
                    $value = self::plain((string)($item->cValue ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $key   = $name . ':' . $value;
                    // bSet from JTL Search; the request decides too, so a value is never added twice
                    $isSet = (bool)($item->bSet ?? false) || \count($this->without($key)) !== \count($this->entries);
                    $url   = $isSet
                        ? $this->buildUrl($this->without($key))
                        : $this->buildUrl([...$this->entries, self::entry($key, true)]);
                    $child = new Option();
                    $child->setValue($value);
                    $child->setName($value);
                    $child->setFrontendName($value);
                    $child->setURL($url);
                    $child->setCount((int)($item->nCount ?? 0));
                    $child->setIsActive($isSet);
                    $option->addOption($child);
                    $values[] = $value;
                    if ($isSet) {
                        $option->setIsActive(true);
                        $active[] = ['label' => $value, 'url' => $url];
                    }
                }
                if (\count($values) === 0) {
                    continue;
                }
                $display = $config['display'] ?? JtlSearchConfigRepository::DISPLAY_AUTO;
                if (
                    $display === JtlSearchConfigRepository::DISPLAY_BUTTONS
                    || ($display === JtlSearchConfigRepository::DISPLAY_AUTO && $type === self::TYPE_TINYBOX)
                ) {
                    $buttons[$id] = [
                        'characteristicID' => $id,
                        'columns'          => \Plugin\feature_sliders\Bootstrap::buttonColumns($values),
                        'tpl'              => $this->frontendPath . 'tpl/buttons.tpl',
                    ];
                }
            }
            if ($config !== null && $config['expanded']) {
                $option->setIsActive(true);
            }
            $options[] = $option;
            // configured order (backend tab "JTL Search"); groups not recorded yet keep JTL's order at the end
            $order[] = $config !== null ? $config['sort'] : 1000000 + $index;
        }
        $this->repository?->record($seen);
        if (\count($options) === 0) {
            return null;
        }
        // stable sort by configured position (array_multisort would compare the option objects on ties)
        $positions = \array_keys($options);
        \usort($positions, static fn(int $a, int $b): int => [$order[$a], $a] <=> [$order[$b], $b]);
        $options = \array_map(static fn(int $i): CharacteristicOption => $options[$i], $positions);
        $results    = $productFilter->getSearchResults();
        $collection = $productFilter->getCharacteristicFilterCollection();
        $results->setCharacteristicFilterOptions($options);
        $collection->setOptions($options);
        $collection->setFilterCollection($options);
        if ($collection->isHidden()) {
            $collection->setVisibility($useFilter);
        }
        $smarty->assign('mrfJtl', [
            'options'   => $options,
            'active'    => $active,
            'removeAll' => \count($this->entries) > 0 ? $this->baseUrl : null,
            'url'       => $this->buildUrl($this->entries),
            'mobileTpl' => $this->frontendPath . 'tpl/jtl_mobile.tpl',
        ]);
        // JTL's box "Filter (JTL Search)" renders only while this variable is set – avoid showing the filters twice
        $smarty->clearAssign('oExtendedJTLSearchResponse');
        $this->prepared = true;

        return ['sliders' => $sliders, 'buttons' => $buttons];
    }

    /**
     * HOOK_FILTER_ENDE (after jtl_search rebuilt the sorting options in HOOK_FILTER_PAGE, before the list is
     * rendered): paging, sorting and items-per-page links keep the active JTL Search filters.
     */
    public function keepFiltersInLinks(ProductFilter $productFilter, JTLSmarty $smarty): void
    {
        if (!$this->prepared || \count($this->entries) === 0) {
            return;
        }
        $results = $productFilter->getSearchResults();
        foreach ([$results->getSortingOptions(), $results->getLimitOptions()] as $list) {
            foreach ($list as $option) {
                if ($option instanceof Option) {
                    $option->setURL($this->appendFilters($option->getURL()));
                }
            }
        }
        $pagination = $smarty->getTemplateVars('filterPagination');
        if (\is_object($pagination) && \method_exists($pagination, 'getPages')) {
            $items = [...$pagination->getPages(), $pagination->getPrev(), $pagination->getNext()];
            foreach ($items as $item) {
                if (\is_object($item) && \method_exists($item, 'setURL')) {
                    $item->setURL($this->appendFilters($item->getURL()));
                }
            }
        }
    }

    /**
     * Appends "fq0=…&fq1=…" once; keeps the URL's own separator style (the core builds paging URLs with "&amp;").
     */
    public function appendFilters(?string $url): ?string
    {
        if ($url === null || $url === '' || \count($this->entries) === 0 || \preg_match('/[?&;]fq0=/', $url)) {
            return $url;
        }
        $amp    = \str_contains($url, '&amp;') ? '&amp;' : '&';
        $params = [];
        foreach (\array_values($this->entries) as $i => $entry) {
            $params[] = 'fq' . $i . '=' . $entry['enc'];
        }

        return $url . (\str_contains($url, '?') ? $amp : '?') . \implode($amp, $params);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<int, array{key: string, enc: string}>
     */
    public static function requestEntries(array $query): array
    {
        $entries = [];
        for ($i = 0; $i < self::MAX_FILTERS; $i++) {
            $raw = $query['fq' . $i] ?? null;
            // group names are JTL Search field names (attr_str_warengruppe) – anything else is not ours
            if (!\is_string($raw) || !\preg_match('/^' . self::GROUP_PATTERN . ':/', $raw)) {
                continue;
            }
            $entries[] = self::entry($raw, false);
        }

        return $entries;
    }

    /**
     * One filter "group:value". $fromResponse: value is UTF-8 from the JTL Search response and gets encoded
     * ISO-8859-1 like JtlSearch::extendFilterItemURL(); otherwise the raw request bytes are kept as they came.
     *
     * @return array{key: string, enc: string}
     */
    public static function entry(string $filter, bool $fromResponse): array
    {
        [$group, $value] = \explode(':', $filter, 2);
        $utf8            = \mb_check_encoding($value, 'UTF-8')
            ? $value
            : (string)\mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $bytes           = $fromResponse ? (string)\mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8') : $value;

        return ['key' => $group . ':' . $utf8, 'enc' => $group . ':' . \urlencode($bytes)];
    }

    /**
     * @param array<int, array{key: string, enc: string}> $entries
     */
    public function buildUrl(array $entries): string
    {
        $url = $this->baseUrl;
        foreach (\array_values($entries) as $i => $entry) {
            $url .= '&fq' . $i . '=' . $entry['enc'];
        }

        return $url;
    }

    public function setBaseUrl(string $url): void
    {
        $this->baseUrl = $url;
    }

    /**
     * @param array<int, array{key: string, enc: string}> $entries
     */
    public function setEntries(array $entries): void
    {
        $this->entries  = $entries;
        $this->prepared = true;
    }

    /**
     * @return array<int, array{key: string, enc: string}>
     */
    private function without(string $key): array
    {
        return \array_values(\array_filter($this->entries, static fn(array $e): bool => $e['key'] !== $key));
    }

    /**
     * @return array<int, array{key: string, enc: string}>
     */
    private function withoutGroup(string $group): array
    {
        return \array_values(\array_filter(
            $this->entries,
            static fn(array $e): bool => !\str_starts_with($e['key'], $group . ':')
        ));
    }

    /**
     * @param array{from: string, to: string} $labels
     * @param array<string, mixed>|null       $config unit/step from the backend tab override JTL Search's
     * @return array<string, mixed>|null
     */
    private function sliderData(stdClass $group, string $name, array $labels, ?array $config = null): ?array
    {
        $item = $group->oFilterItem_arr[0] ?? null;
        if (!\is_object($item)) {
            return null;
        }
        $min  = self::number($item->fFrom ?? null);
        $max  = self::number($item->fTo ?? null);
        $step = ($config['step'] ?? 0) > 0 ? (float)$config['step'] : self::number($item->fStep ?? null);
        if ($min === null || $max === null || $max <= $min) {
            return null;
        }
        $from      = self::number($item->cStateFrom ?? null) ?? $min;
        $to        = self::number($item->cStateTo ?? null) ?? $max;
        $remaining = $this->withoutGroup($name);
        $isActive  = \count($remaining) !== \count($this->entries);

        return [
            'tpl'     => $this->frontendPath . 'tpl/slider.tpl',
            'param'   => 'fq' . \count($remaining),
            // exactly as JTL's box prints {$oFilterItem->fFrom}_{$oFilterItem->fTo}
            'prefix'  => $name . ':' . (string)$item->fFrom . '_' . (string)$item->fTo . '-',
            'baseUrl' => $this->buildUrl($remaining),
            'min'     => self::numberString($min),
            'max'     => self::numberString($max),
            'from'    => self::numberString(\max($min, \min($from, $max))),
            'to'      => self::numberString(\max($min, \min($to, $max))),
            'step'    => self::numberString($step !== null && $step > 0 ? $step : 1),
            'unit'    => ($config['unit'] ?? '') !== '' ? $config['unit'] : self::plain((string)($item->cUnit ?? '')),
            'labels'  => $labels,
            'active'  => $isActive,
        ];
    }

    private function createGroup(int $id, string $title): CharacteristicOption
    {
        $option = new CharacteristicOption();
        $option->setID($id);
        $option->setValue($id);
        $option->setName($title);
        $option->setFrontendName($title);
        $option->setData('kMerkmal', $id)
            ->setData('cTyp', 'TEXT')
            ->setData('isMultiSelect', false);
        $option->setCount(1);

        return $option;
    }

    private function query(ProductFilter $productFilter, stdClass $response): string
    {
        foreach (['suchausdruck', 'qs', 'suche'] as $param) {
            if (isset($_GET[$param]) && \is_string($_GET[$param]) && \trim($_GET[$param]) !== '') {
                return \trim($_GET[$param]);
            }
        }
        $name = $productFilter->getSearch()->getName();
        if (\is_string($name) && $name !== '') {
            return $name;
        }

        return (string)($response->oSearch->cQuery ?? '');
    }

    private static function number(mixed $value): ?float
    {
        if (\is_int($value) || \is_float($value)) {
            return (float)$value;
        }
        if (\is_string($value) && \is_numeric(\str_replace(',', '.', \trim($value)))) {
            return (float)\str_replace(',', '.', \trim($value));
        }

        return null;
    }

    /**
     * Same text as Smarty/PHP prints a float in JTL's box template ("35", "35.5").
     */
    private static function numberString(mixed $value): string
    {
        $number = self::number($value);

        return $number === null ? (string)$value : (string)$number;
    }

    private static function plain(string $text): string
    {
        return \trim(\html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
    }
}
