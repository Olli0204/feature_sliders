<?php

declare(strict_types=1);

namespace Plugin\feature_sliders;

use JTL\Events\Dispatcher;
use JTL\Filter\CharacteristicOption;
use JTL\Filter\ProductFilter;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\feature_sliders\Filter\CharacteristicConfig;
use Plugin\feature_sliders\Filter\ConfigRepository;
use Plugin\feature_sliders\Filter\RangeFilter;
use Plugin\feature_sliders\Filter\RangeParser;
use Plugin\feature_sliders\Filter\Settings;

class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        $settings = Settings::fromPlugin($this->getPlugin());
        Settings::set($settings);
        if (!$settings->active) {
            return;
        }
        $dispatcher->hookInto(\HOOK_PRODUCTFILTER_CREATE, function (array $args): void {
            $productFilter = $args['productFilter'] ?? null;
            if (!$productFilter instanceof ProductFilter) {
                return;
            }
            foreach ($this->getRepository()->getSliders() as $config) {
                $productFilter->registerFilter(new RangeFilter($productFilter, $config));
            }
        });
        $dispatcher->hookInto(\HOOK_FILTER_PAGE, function (): void {
            $this->prepareListing();
        });
    }

    private function getRepository(): ConfigRepository
    {
        return new ConfigRepository($this->getDB(), $this->getCache(), $this->getPlugin()->getCache()->getGroup());
    }

    /**
     * Runs after the search results are built and before the boxes are rendered: hands the
     * slider data to Smarty ($mrfSliders, keyed by kMerkmal) and makes sure each characteristic
     * shows up in the core characteristic filter, where the plugin templates swap its values
     * for the slider.
     */
    private function prepareListing(): void
    {
        $productFilter = Shop::getProductFilter();
        $l10n          = $this->getPlugin()->getLocalization();
        $labels        = [
            'from' => $l10n->getTranslation('mrf_from') ?? 'Von',
            'to'   => $l10n->getTranslation('mrf_to') ?? 'Bis',
        ];
        $sliders       = [];
        foreach ($productFilter->getAvailableFilters() as $filter) {
            if (!$filter instanceof RangeFilter || ($slider = $filter->getSliderData()) === null) {
                continue;
            }
            $slider['labels']                     = $labels;
            $sliders[$filter->getCharacteristicID()] = $slider;
            $this->placeInCharacteristicFilter($productFilter, $filter);
        }
        if (\count($sliders) > 0) {
            Shop::Smarty()->assign('mrfSliders', $sliders);
        }
    }

    /**
     * The core lists a characteristic only when products of the current result carry one of its
     * values (e.g. not with 0 hits after narrowing the slider). In that case a placeholder option
     * is added so the slider stays reachable. The option is flagged active when the slider should
     * be expanded or the filter is set.
     */
    private function placeInCharacteristicFilter(ProductFilter $productFilter, RangeFilter $filter): void
    {
        $characteristicID = $filter->getCharacteristicID();
        $results          = $productFilter->getSearchResults();
        $collection       = $productFilter->getCharacteristicFilterCollection();
        $options          = $results->getCharacteristicFilterOptions();
        $option           = null;
        foreach ($options as $candidate) {
            if ((int)$candidate->getValue() === $characteristicID) {
                $option = $candidate;
                break;
            }
        }
        if ($option === null) {
            $useFilter = (string)($productFilter->getFilterConfig()->getConfig('navigationsfilter')
                ['merkmalfilter_verwenden'] ?? 'N');
            if ($useFilter === 'N') {
                return;
            }
            $option = new CharacteristicOption();
            $option->setID($characteristicID);
            $option->setValue($characteristicID);
            $option->setName($filter->getFrontendName());
            $option->setFrontendName($filter->getFrontendName());
            $option->setParam($collection->getUrlParam());
            $option->setClassName($collection->getClassName());
            $option->setType($collection->getType());
            $option->setData('kMerkmal', $characteristicID)
                ->setData('cTyp', 'TEXT')
                ->setData('isMultiSelect', false);
            $option->setCount(1);
            $options[] = $option;
            $results->setCharacteristicFilterOptions($options);
            $collection->setOptions($options);
            $collection->setFilterCollection($options);
            if ($collection->isHidden()) {
                $collection->setVisibility($useFilter);
            }
        }
        if ($filter->getCharacteristicConfig()?->expanded || $filter->isInitialized()) {
            $option->setIsActive(true);
        }
    }

    /**
     * @inheritdoc
     */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        $plugin     = $this->getPlugin();
        $repository = $this->getRepository();
        $saved      = null;
        if (Request::postInt('mrf_save') === 1) {
            $saved = Form::validateToken();
            if ($saved) {
                $posted = Request::postVar('mrf', []);
                $repository->saveAll(\array_map(
                    static fn($id, $data): CharacteristicConfig => CharacteristicConfig::fromArray(
                        (int)$id,
                        \is_array($data) ? $data : []
                    ),
                    \array_keys(\is_array($posted) ? $posted : []),
                    \array_values(\is_array($posted) ? $posted : [])
                ));
            }
        }
        $configs = $repository->getAll();

        return $smarty->assign('mrfRows', $this->getAdminRows($configs))
            ->assign('mrfMenuID', $menuID)
            ->assign('mrfSaved', $saved)
            ->assign('mrfActive', Settings::fromPlugin($plugin)->active)
            ->assign('mrfDisplays', [
                CharacteristicConfig::DISPLAY_DEFAULT       => 'Standard-Filter (Checkboxen)',
                CharacteristicConfig::DISPLAY_SLIDER_RANGE  => 'Schieberegler: Bereich auf Bereich',
                CharacteristicConfig::DISPLAY_SLIDER_SINGLE => 'Schieberegler: Bereich auf Einzelwerte',
            ])
            ->assign('mrfModes', [
                RangeParser::MODE_OVERLAP  => 'Bereiche überschneiden sich',
                RangeParser::MODE_CONTAINS => 'Artikel-Bereich enthält die Auswahl',
                RangeParser::MODE_WITHIN   => 'Artikel-Bereich liegt in der Auswahl',
            ])
            ->fetch($plugin->getPaths()->getAdminPath() . 'templates/characteristics.tpl');
    }

    /**
     * One row per characteristic of the shop; value analysis only for configured sliders.
     *
     * @param array<int, CharacteristicConfig> $configs
     * @return array<int, array<string, mixed>>
     */
    private function getAdminRows(array $configs): array
    {
        $db   = $this->getDB();
        $rows = [];
        $data = $db->getObjects(
            "SELECT m.kMerkmal, m.cName, COUNT(DISTINCT mw.kMerkmalWert) AS valueCount,
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(DISTINCT mws.cWert ORDER BY mw.nSort, mws.cWert SEPARATOR '\n'), '\n', 4
                    ) AS samples
                FROM tmerkmal AS m
                LEFT JOIN tmerkmalwert AS mw ON mw.kMerkmal = m.kMerkmal
                LEFT JOIN tmerkmalwertsprache AS mws ON mws.kMerkmalWert = mw.kMerkmalWert
                    AND mws.kSprache = (SELECT kSprache FROM tsprache WHERE cShopStandard = 'Y' LIMIT 1)
                GROUP BY m.kMerkmal, m.cName, m.nSort
                ORDER BY m.nSort, m.cName"
        );
        foreach ($data as $row) {
            $id     = (int)$row->kMerkmal;
            $config = $configs[$id] ?? new CharacteristicConfig($id, CharacteristicConfig::DISPLAY_DEFAULT);
            $item   = [
                'id'         => $id,
                'name'       => (string)$row->cName,
                'valueCount' => (int)$row->valueCount,
                'samples'    => \array_values(\array_filter(\explode("\n", (string)$row->samples))),
                'config'     => $config,
                'values'     => [],
                'invalid'    => 0,
                'unit'       => '',
                'bounds'     => null,
            ];
            if ($config->isSlider()) {
                $item = \array_merge($item, $this->analyseValues($config));
            }
            $rows[] = $item;
        }

        return $rows;
    }

    /**
     * @return array{values: array<int, array<string, mixed>>, invalid: int, unit: string, bounds: array|null}
     */
    private function analyseValues(CharacteristicConfig $config): array
    {
        $db       = $this->getDB();
        $parsed   = RangeFilter::loadValueData($db, $config->characteristicID, $config->isSingleValue());
        $products = [];
        foreach (
            $db->getObjects(
                'SELECT kMerkmalWert, COUNT(DISTINCT kArtikel) AS cnt
                    FROM tartikelmerkmal WHERE kMerkmal = :cid GROUP BY kMerkmalWert',
                ['cid' => $config->characteristicID]
            ) as $row
        ) {
            $products[(int)$row->kMerkmalWert] = (int)$row->cnt;
        }
        $values  = [];
        $invalid = 0;
        foreach ($parsed['values'] as $valueID => $value) {
            $range = $value['range'];
            if ($range === null) {
                ++$invalid;
            }
            $values[] = [
                'text'     => $value['text'],
                'products' => $products[$valueID] ?? 0,
                'ok'       => $range !== null,
                'min'      => $range === null ? '–' : ($range['min'] === null
                    ? 'offen'
                    : RangeFilter::formatNumber($range['min'])),
                'max'      => $range === null ? '–' : ($range['max'] === null
                    ? 'offen'
                    : RangeFilter::formatNumber($range['max'])),
            ];
        }

        return [
            'values'  => $values,
            'invalid' => $invalid,
            'unit'    => $parsed['unit'],
            'bounds'  => RangeParser::bounds(\array_values($parsed['ranges']), $config->step),
        ];
    }
}
