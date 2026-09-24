<?php

declare(strict_types=1);

namespace Plugin\feature_sliders;

use JTL\Events\Dispatcher;
use JTL\Filter\CharacteristicOption;
use JTL\Filter\ProductFilter;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
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
        if (!$settings->isUsable()) {
            return;
        }
        $dispatcher->hookInto(\HOOK_PRODUCTFILTER_CREATE, function (array $args): void {
            $productFilter = $args['productFilter'] ?? null;
            if ($productFilter instanceof ProductFilter) {
                $productFilter->registerFilter(new RangeFilter($productFilter));
            }
        });
        $dispatcher->hookInto(\HOOK_FILTER_PAGE, function () use ($settings): void {
            $this->prepareListing($settings);
        });
    }

    /**
     * Runs after the search results are built and before the boxes are rendered: hands the
     * slider data to Smarty and makes sure the characteristic shows up in the core
     * characteristic filter, where the plugin templates swap its values for the slider.
     */
    private function prepareListing(Settings $settings): void
    {
        $productFilter = Shop::getProductFilter();
        $filter        = $productFilter->getFilterByClassName(RangeFilter::class);
        if (!$filter instanceof RangeFilter) {
            return;
        }
        $slider = $filter->getSliderData();
        if ($slider === null) {
            return;
        }
        $l10n             = $this->getPlugin()->getLocalization();
        $slider['labels'] = [
            'from' => $l10n->getTranslation('mrf_from') ?? 'Von',
            'to'   => $l10n->getTranslation('mrf_to') ?? 'Bis',
        ];
        Shop::Smarty()->assign('mrfSlider', $slider);
        $this->placeInCharacteristicFilter($productFilter, $settings, $filter);
    }

    /**
     * The core lists a characteristic only when products of the current result carry one of its
     * values (e.g. not with 0 hits after narrowing the slider). In that case a placeholder option
     * is added so the slider stays reachable. The option is flagged active when the slider should
     * be expanded or the filter is set.
     */
    private function placeInCharacteristicFilter(
        ProductFilter $productFilter,
        Settings $settings,
        RangeFilter $filter
    ): void {
        $results    = $productFilter->getSearchResults();
        $collection = $productFilter->getCharacteristicFilterCollection();
        $options    = $results->getCharacteristicFilterOptions();
        $option     = null;
        foreach ($options as $candidate) {
            if ((int)$candidate->getValue() === $settings->characteristicID) {
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
            $option->setID($settings->characteristicID);
            $option->setValue($settings->characteristicID);
            $option->setName($filter->getFrontendName());
            $option->setFrontendName($filter->getFrontendName());
            $option->setParam($collection->getUrlParam());
            $option->setClassName($collection->getClassName());
            $option->setType($collection->getType());
            $option->setData('kMerkmal', $settings->characteristicID)
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
        if ($settings->expanded || $filter->isInitialized()) {
            $option->setIsActive(true);
        }
    }

    /**
     * @inheritdoc
     */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        $plugin   = $this->getPlugin();
        $settings = Settings::fromPlugin($plugin);
        $rows     = [];
        $name     = '';
        $bounds   = null;
        if ($settings->characteristicID > 0) {
            $db   = $this->getDB();
            $name = (string)($db->getSingleObject(
                'SELECT cName FROM tmerkmal WHERE kMerkmal = :cid',
                ['cid' => $settings->characteristicID]
            )->cName ?? '');
            $data = $db->getObjects(
                "SELECT mw.kMerkmalWert, mws.cWert, COUNT(DISTINCT am.kArtikel) AS products
                    FROM tmerkmalwert AS mw
                    JOIN tmerkmalwertsprache AS mws ON mws.kMerkmalWert = mw.kMerkmalWert
                    JOIN tsprache AS sp ON sp.kSprache = mws.kSprache AND sp.cShopStandard = 'Y'
                    LEFT JOIN tartikelmerkmal AS am ON am.kMerkmalWert = mw.kMerkmalWert
                    WHERE mw.kMerkmal = :cid
                    GROUP BY mw.kMerkmalWert, mws.cWert
                    ORDER BY mw.nSort, mws.cWert",
                ['cid' => $settings->characteristicID]
            );
            $parsed = RangeFilter::loadValueRanges($db, $settings->characteristicID);
            foreach ($data as $row) {
                $range  = $parsed[(int)$row->kMerkmalWert] ?? null;
                $rows[] = [
                    'id'       => (int)$row->kMerkmalWert,
                    'value'    => (string)$row->cWert,
                    'products' => (int)$row->products,
                    'ok'       => $range !== null,
                    'min'      => $range === null ? '–' : ($range['min'] === null
                        ? 'offen'
                        : RangeFilter::formatNumber($range['min'])),
                    'max'      => $range === null ? '–' : ($range['max'] === null
                        ? 'offen'
                        : RangeFilter::formatNumber($range['max'])),
                ];
            }
            $bounds = RangeParser::bounds(\array_values($parsed), $settings->step);
        }

        return $smarty->assign('mrfRows', $rows)
            ->assign('mrfSettings', $settings)
            ->assign('mrfCharacteristicName', $name)
            ->assign('mrfBounds', $bounds)
            ->assign('mrfInvalid', \count(\array_filter($rows, static fn(array $r): bool => !$r['ok'])))
            ->fetch($plugin->getPaths()->getAdminPath() . 'templates/overview.tpl');
    }
}
