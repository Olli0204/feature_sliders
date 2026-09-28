<?php

declare(strict_types=1);

namespace Plugin\feature_sliders;

use JTL\Events\Dispatcher;
use JTL\Filter\CharacteristicOption;
use JTL\Filter\ProductFilter;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\feature_sliders\Admin\CharacteristicsAdmin;
use Plugin\feature_sliders\Filter\ConfigRepository;
use Plugin\feature_sliders\Filter\RangeFilter;
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
        $this->prepareButtons($productFilter);
    }

    /**
     * Characteristics shown as button boxes keep the core values, URLs and active states – only the
     * markup changes (plugin templates). Here they are flagged for the templates and expanded if configured.
     */
    private function prepareButtons(ProductFilter $productFilter): void
    {
        $buttons = $this->getRepository()->getButtons();
        if (\count($buttons) === 0) {
            return;
        }
        $settings = Settings::get();
        $boxes    = [];
        foreach ($productFilter->getSearchResults()->getCharacteristicFilterOptions() as $option) {
            $config = $buttons[(int)$option->getValue()] ?? null;
            if ($config === null) {
                continue;
            }
            $boxes[$config->characteristicID] = [
                'characteristicID' => $config->characteristicID,
                'columns'          => $config->buttonColumns > 0
                    ? $config->buttonColumns
                    : self::buttonColumns(\array_map(
                        static fn($value): string => (string)$value->getValue(),
                        $option->getOptions()
                    )),
                'tpl'              => ($settings?->frontendPath ?? '') . 'tpl/buttons.tpl',
            ];
            if ($config->expanded) {
                $option->setIsActive(true);
            }
        }
        if (\count($boxes) > 0) {
            Shop::Smarty()->assign('mrfButtons', $boxes);
        }
    }

    /**
     * Equal-width grid: the longest value decides how many boxes fit next to each other
     * (sidebar ≈ 250–300 px), so long values are not squeezed into narrow boxes.
     *
     * @param string[] $labels
     */
    public static function buttonColumns(array $labels): int
    {
        $longest = 0;
        foreach ($labels as $label) {
            $longest = \max($longest, \mb_strlen(\trim(\html_entity_decode($label, \ENT_QUOTES, 'UTF-8'))));
        }

        return match (true) {
            $longest <= 3  => 4,
            $longest <= 9  => 3,
            $longest <= 16 => 2,
            default        => 1,
        };
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
        return (new CharacteristicsAdmin($this->getPlugin(), $this->getDB(), $this->getRepository()))
            ->render($menuID, $smarty);
    }
}
