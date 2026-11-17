<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Admin;

use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Plugin\PluginInterface;
use JTL\Smarty\JTLSmarty;
use Plugin\feature_sliders\Bootstrap;
use Plugin\feature_sliders\Filter\CharacteristicConfig;
use Plugin\feature_sliders\Filter\ConfigRepository;
use Plugin\feature_sliders\Filter\OrderRepository;
use Plugin\feature_sliders\Filter\RangeFilter;
use Plugin\feature_sliders\Filter\RangeParser;
use Plugin\feature_sliders\Filter\Settings;

/**
 * Backend tab "Merkmale": overview of the configured characteristics with an active switch,
 * add/edit dialogs with live preview and value check.
 */
final class CharacteristicsAdmin
{
    public const DISPLAY_LABELS = [
        CharacteristicConfig::DISPLAY_SLIDER_RANGE  => 'Schieberegler: Bereich auf Bereich',
        CharacteristicConfig::DISPLAY_SLIDER_SINGLE => 'Schieberegler: Bereich auf Einzelwerte',
        CharacteristicConfig::DISPLAY_BUTTONS       => 'Boxen',
    ];

    /** POST actions of this tab (mrf_action) */
    public const ACTIONS = ['char_save', 'char_toggle', 'char_remove', 'order_save', 'order_reset'];

    public const MODE_LABELS = [
        RangeParser::MODE_OVERLAP  => 'Bereiche überschneiden sich',
        RangeParser::MODE_CONTAINS => 'Artikel-Bereich enthält die Auswahl',
        RangeParser::MODE_WITHIN   => 'Artikel-Bereich liegt in der Auswahl',
    ];

    /** @var \Closure(): bool */
    private readonly \Closure $tokenValidator;

    public function __construct(
        private readonly PluginInterface $plugin,
        private readonly DbInterface $db,
        private readonly ConfigRepository $repository,
        ?\Closure $tokenValidator = null,
        private readonly ?OrderRepository $order = null
    ) {
        $this->tokenValidator = $tokenValidator ?? static fn(): bool => Form::validateToken();
    }

    public function render(int $menuID, JTLSmarty $smarty): string
    {
        $characteristics = $this->loadCharacteristics();
        [$message, $openEdit] = $this->handlePost($characteristics);
        $configs    = $this->repository->getAll();
        $orderIDs   = $this->order?->getOrder() ?? [];
        $configured = [];
        $available  = [];
        foreach ($characteristics as $id => $characteristic) {
            if (isset($configs[$id])) {
                $configured[] = $this->buildRow($characteristic, $configs[$id]);
            } else {
                $available[] = $characteristic;
            }
        }

        return $smarty->assign('mrfRows', $configured)
            ->assign('mrfAvailable', $available)
            ->assign('mrfMenuID', $menuID)
            ->assign('mrfMessage', $message)
            ->assign('mrfOpenEdit', $openEdit)
            ->assign('mrfActive', Settings::fromPlugin($this->plugin)->active)
            ->assign('mrfDisplayLabels', self::DISPLAY_LABELS)
            ->assign('mrfModeLabels', self::MODE_LABELS)
            ->assign('mrfOrder', self::orderList($characteristics, $orderIDs, $configs))
            ->assign('mrfOrderCustom', \count($orderIDs) > 0)
            ->assign('mrfOrderTpl', $this->plugin->getPaths()->getAdminPath() . 'templates/order.tpl')
            ->assign('mrfTilesTpl', $this->plugin->getPaths()->getAdminPath() . 'templates/display_tiles.tpl')
            ->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/characteristics.tpl');
    }

    /**
     * @param array<int, array<string, mixed>> $characteristics
     * @return array{0: array{type: string, text: string}|null, 1: int}
     */
    private function handlePost(array $characteristics): array
    {
        // JTL renders every plugin tab on each request, so a POST of the "Filtergruppen" tab reaches this
        // tab as well – only react to our own actions
        $action = (string)Request::postVar('mrf_action', '');
        if (!\in_array($action, self::ACTIONS, true)) {
            return [null, 0];
        }
        if (!($this->tokenValidator)()) {
            return [['type' => 'danger', 'text' => 'Ungültiges Sicherheits-Token. Bitte die Seite neu laden und erneut versuchen.'], 0];
        }
        if ($action === 'order_save' || $action === 'order_reset') {
            if ($this->order === null) {
                return [null, 0];
            }
            if ($action === 'order_reset') {
                $this->order->reset();

                return [['type' => 'success', 'text' => 'Reihenfolge zurückgesetzt – die Filter erscheinen wieder in der Wawi-Reihenfolge (bzw. der Reihenfolge der Filtergruppe).'], 0];
            }
            $ids = \array_values(\array_filter(
                \array_map('\intval', (array)Request::postVar('mrf_order', [])),
                static fn(int $cid): bool => isset($characteristics[$cid])
            ));
            $this->order->save($ids);

            return [['type' => 'success', 'text' => 'Reihenfolge der Filter gespeichert. Auf bereits zwischengespeicherten Seiten wirkt sie erst nach dem Leeren des LiteSpeed-Caches.'], 0];
        }
        $id   = (int)Request::postInt('mrf_id', 0);
        $name = (string)($characteristics[$id]['name'] ?? '');
        if ($name === '') {
            return [['type' => 'danger', 'text' => 'Dieses Merkmal existiert nicht (mehr).'], 0];
        }
        $existing = $this->repository->get($id);
        switch ($action) {
            case 'char_save':
                $data   = Request::postVar('mrf', []);
                $config = CharacteristicConfig::fromArray($id, \is_array($data) ? $data : [])
                    ->withActive($existing?->active ?? true);
                if (!$config->isSlider() && !$config->isButtons()) {
                    return [['type' => 'danger', 'text' => 'Bitte eine Darstellung wählen.'], 0];
                }
                $this->repository->save($config);

                return [
                    ['type' => 'success', 'text' => '„' . $name . '“ ' . ($existing === null ? 'hinzugefügt.' : 'gespeichert.')],
                    // after adding, open the edit dialog so the preview and value check are visible right away
                    $existing === null ? $id : 0,
                ];
            case 'char_toggle':
                if ($existing === null) {
                    return [null, 0];
                }
                $active = Request::postInt('mrf_active', 0) === 1;
                $this->repository->setActive($id, $active);

                return [[
                    'type' => 'success',
                    'text' => '„' . $name . '“ ' . ($active
                        ? 'aktiviert.'
                        : 'deaktiviert – im Shop erscheint wieder der normale Merkmalfilter.'),
                ], 0];
            case 'char_remove':
                $this->repository->remove($id);

                return [['type' => 'success', 'text' => '„' . $name . '“ entfernt – im Shop erscheint wieder der normale Merkmalfilter.'], 0];
        }

        return [null, 0];
    }

    /**
     * All characteristics for the section "Reihenfolge der Filter": saved order first, the rest in Wawi order.
     *
     * @param array<int, array<string, mixed>>   $characteristics Wawi order
     * @param int[]                              $orderIDs
     * @param array<int, CharacteristicConfig>   $configs
     * @return array<int, array{id: int, name: string, samples: string[], display: string}>
     */
    public static function orderList(array $characteristics, array $orderIDs, array $configs): array
    {
        $sorted = OrderRepository::sortOptions(
            \array_map(static fn(array $c): object => new class ($c) {
                public function __construct(public array $c)
                {
                }

                public function getValue(): int
                {
                    return (int)$this->c['id'];
                }
            }, \array_values($characteristics)),
            $orderIDs
        );
        $result = [];
        foreach ($sorted as $item) {
            $c      = $item->c;
            $config = $configs[$c['id']] ?? null;
            $result[] = [
                'id'      => (int)$c['id'],
                'name'    => (string)$c['name'],
                'samples' => \array_slice($c['samples'] ?? [], 0, 3),
                'display' => $config === null || !$config->active ? '' : ($config->isButtons() ? 'Boxen' : 'Regler'),
            ];
        }

        return $result;
    }

    /**
     * All characteristics of the shop in Wawi order, with a few sample values.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadCharacteristics(): array
    {
        $rows   = $this->db->getObjects(
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
        $result = [];
        foreach ($rows as $row) {
            $id          = (int)$row->kMerkmal;
            $result[$id] = [
                'id'         => $id,
                'name'       => (string)$row->cName,
                'valueCount' => (int)$row->valueCount,
                'samples'    => \array_values(\array_filter(\explode("\n", (string)$row->samples))),
            ];
        }

        return $result;
    }

    /**
     * Overview row + everything the edit dialog needs (value check for both slider types,
     * preview data as JSON).
     *
     * @param array<string, mixed> $characteristic
     * @return array<string, mixed>
     */
    private function buildRow(array $characteristic, CharacteristicConfig $config): array
    {
        $id       = $characteristic['id'];
        $products = $this->productCounts($id);
        $range    = RangeFilter::loadValueData($this->db, $id, false);
        $single   = RangeFilter::loadValueData($this->db, $id, true);
        $texts    = \array_values(\array_map(static fn(array $v): string => $v['text'], $range['values']));
        $analysis = [
            'range'  => $this->analyse($range, $products),
            'single' => $this->analyse($single, $products),
        ];
        $current  = $config->isSingleValue() ? $analysis['single'] : $analysis['range'];
        $unit     = $config->unit !== '' ? $config->unit : $current['unit'];
        $auto     = Bootstrap::buttonColumns($texts);
        $columns  = $config->buttonColumns > 0 ? $config->buttonColumns : $auto;

        if ($config->isButtons()) {
            $summary = \count($texts) . ' Werte · ' . $columns . ' ' . ($columns === 1 ? 'Spalte' : 'Spalten')
                . ($config->buttonColumns > 0 ? '' : ' (automatisch)');
        } else {
            $step    = RangeFilter::formatNumber($config->step);
            $summary = $current['bounds'] !== null
                ? RangeFilter::formatNumber($current['bounds']['min']) . ' – '
                    . RangeFilter::formatNumber($current['bounds']['max']) . ($unit !== '' ? ' ' . $unit : '')
                : 'keine auswertbaren Werte';
            $summary .= ' · Schritt ' . $step;
            if (!$config->isSingleValue()) {
                $summary .= ' · ' . self::MODE_LABELS[$config->mode];
            }
        }
        $summary .= $config->expanded ? ' · aufgeklappt' : '';

        return $characteristic + [
            'config'      => $config,
            'summary'     => $summary,
            'analysis'    => $analysis,
            'autoColumns' => $auto,
            'invalid'     => $config->isSlider() ? $current['invalid'] : 0,
            'preview'     => \json_encode([
                'name'        => $characteristic['name'],
                'values'      => $texts,
                'autoColumns' => $auto,
                'range'       => [
                    'extent' => $analysis['range']['extent'],
                    'unit'   => $analysis['range']['unit'],
                ],
                'single'      => [
                    'extent' => $analysis['single']['extent'],
                    'unit'   => $analysis['single']['unit'],
                ],
            ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT),
        ];
    }

    /**
     * @param array{ranges: array, unit: string, values: array} $data
     * @param array<int, int> $products
     * @return array<string, mixed>
     */
    private function analyse(array $data, array $products): array
    {
        $rows    = [];
        $invalid = 0;
        foreach ($data['values'] as $valueID => $value) {
            $range = $value['range'];
            if ($range === null) {
                ++$invalid;
            }
            $rows[] = [
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
        $ranges = \array_values($data['ranges']);

        return [
            'rows'    => $rows,
            'invalid' => $invalid,
            'unit'    => $data['unit'],
            'extent'  => RangeParser::extent($ranges),
            'bounds'  => RangeParser::bounds($ranges),
        ];
    }

    /**
     * @return array<int, int> kMerkmalWert => number of products
     */
    private function productCounts(int $characteristicID): array
    {
        $counts = [];
        foreach (
            $this->db->getObjects(
                'SELECT kMerkmalWert, COUNT(DISTINCT kArtikel) AS cnt
                    FROM tartikelmerkmal WHERE kMerkmal = :cid GROUP BY kMerkmalWert',
                ['cid' => $characteristicID]
            ) as $row
        ) {
            $counts[(int)$row->kMerkmalWert] = (int)$row->cnt;
        }

        return $counts;
    }
}
