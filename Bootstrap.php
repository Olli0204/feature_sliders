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
     * Runs after the search results are built and before the boxes are rendered:
     * hands the slider data to Smarty and removes the core checkbox filter of the
     * same characteristic.
     */
    private function prepareListing(Settings $settings): void
    {
        $productFilter = Shop::getProductFilter();
        $filter        = $productFilter->getFilterByClassName(RangeFilter::class);
        if (!$filter instanceof RangeFilter) {
            return;
        }
        $slider = $filter->getSliderData();
        if ($slider !== null) {
            $l10n             = $this->getPlugin()->getLocalization();
            $slider['labels'] = [
                'from'  => $l10n->getTranslation('mrf_from') ?? 'Von',
                'to'    => $l10n->getTranslation('mrf_to') ?? 'Bis',
                'reset' => $l10n->getTranslation('mrf_reset') ?? 'Zurücksetzen',
            ];
            Shop::Smarty()->assign('mrfSlider', $slider);
        }
        if (!$settings->hideDefaultFilter) {
            return;
        }
        $results    = $productFilter->getSearchResults();
        $collection = $productFilter->getCharacteristicFilterCollection();
        $keep       = static fn($option): bool => !($option instanceof CharacteristicOption)
            || (int)$option->getValue() !== $settings->characteristicID;
        $results->setCharacteristicFilterOptions(
            \array_values(\array_filter($results->getCharacteristicFilterOptions(), $keep))
        );
        $collection->setOptions(\array_values(\array_filter($collection->getOptions(), $keep)));
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
