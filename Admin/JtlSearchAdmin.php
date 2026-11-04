<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Admin;

use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\feature_sliders\Filter\JtlSearchConfigRepository;
use Plugin\feature_sliders\Filter\Settings;

/**
 * Backend tab "JTL Search": per filter group of the JTL Search plugin (search page) title per language, display,
 * unit, step, order, expanded state and visibility. The groups are recorded by the shop frontend (JtlSearchFilters).
 *
 * Layout like the tab "Merkmale": compact overview (switch and drag order take effect immediately), editing in a
 * dialog, hidden and no longer delivered groups in their own collapsed sections.
 */
final class JtlSearchAdmin
{
    /** POST actions of this tab (mrf_action) */
    public const ACTIONS = ['jtl_edit', 'jtl_toggle', 'jtl_reorder', 'jtl_forget'];

    public const TYPE_LABELS = [1 => 'Checkbox', 2 => 'Regler', 3 => 'Farbbox', 4 => 'Tinybox'];

    public const DISPLAY_LABELS = [
        JtlSearchConfigRepository::DISPLAY_AUTO    => 'wie JTL Search',
        JtlSearchConfigRepository::DISPLAY_LIST    => 'Checkliste',
        JtlSearchConfigRepository::DISPLAY_BUTTONS => 'Boxen',
    ];

    /** a group JTL Search has not delivered for this many days counts as "no longer delivered" */
    public const STALE_DAYS = 14;

    private const SHORT = ['ger' => 'de', 'eng' => 'en', 'fre' => 'fr', 'ita' => 'it', 'spa' => 'es', 'dut' => 'nl'];

    /** @var \Closure(): bool */
    private readonly \Closure $tokenValidator;

    public function __construct(
        private readonly PluginInterface $plugin,
        private readonly JtlSearchConfigRepository $repository,
        private readonly ?DbInterface $db = null,
        ?\Closure $tokenValidator = null
    ) {
        $this->tokenValidator = $tokenValidator ?? static fn(): bool => Form::validateToken();
    }

    public function render(int $menuID, JTLSmarty $smarty): string
    {
        $languages = $this->loadLanguages();
        $message   = $this->handlePost($languages);
        $sections  = ['visible' => [], 'hidden' => [], 'stale' => []];
        foreach ($this->buildRows($languages) as $row) {
            $sections[$row['stale'] ? 'stale' : ($row['active'] ? 'visible' : 'hidden')][] = $row;
        }
        $settings = Settings::fromPlugin($this->plugin);

        return $smarty->assign('mrfJtlSections', $sections)
            ->assign('mrfJtlCount', \array_sum(\array_map('\count', $sections)))
            ->assign('mrfMenuID', $menuID)
            ->assign('mrfMessage', $message)
            ->assign('mrfJtlEnabled', $settings->active && $settings->jtlSearchFilters)
            ->assign('mrfJtlDisplayLabels', self::DISPLAY_LABELS)
            ->assign('mrfJtlLanguages', $languages)
            ->assign('mrfJtlShopURL', $this->shopURL())
            ->assign('mrfJtlStaleDays', self::STALE_DAYS)
            ->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/jtlsearch.tpl');
    }

    /**
     * Rows with everything the overview and the dialog need.
     *
     * @param array<int, array{iso: string, label: string, name: string, default: bool}> $languages
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(array $languages, ?\DateTimeImmutable $today = null): array
    {
        $today   = $today ?? new \DateTimeImmutable('today');
        $default = $languages[0]['iso'] ?? 'ger';
        $rows    = [];
        foreach ($this->repository->getAll() as $row) {
            $isSlider          = $row['jtlType'] === JtlSearchConfigRepository::TYPE_SLIDER;
            $row['isSlider']   = $isSlider;
            $row['typeLabel']  = self::TYPE_LABELS[$row['jtlType']] ?? (string)$row['jtlType'];
            $row['daysAgo']    = $row['lastSeen'] === null
                ? null
                : (int)$today->diff(new \DateTimeImmutable($row['lastSeen']))->days;
            $row['stale']      = $row['daysAgo'] === null || $row['daysAgo'] > self::STALE_DAYS;
            $row['id']         = \substr(\md5($row['name']), 0, 10);
            // titles as shown in the shop, per language
            $row['shopTitles'] = [];
            foreach ($languages as $language) {
                $own = $language['default'] ? $row['title'] : ($row['titles'][$language['iso']] ?? '');
                $row['shopTitles'][] = [
                    'iso'   => $language['iso'],
                    'label' => $language['label'],
                    'name'  => $language['name'],
                    'value' => $own,
                    'shown' => $own !== '' ? $own : ($language['iso'] === $default ? $row['jtlTitle'] : ''),
                    'own'   => $own !== '',
                ];
            }
            $row['mainTitle']  = $row['shopTitles'][0]['shown'] ?? $row['jtlTitle'];
            $row['langLabels'] = \array_values(\array_filter(
                $languages,
                static fn(array $language): bool => \in_array($language['iso'], $row['langs'], true)
            ));
            $row['onlyOneLanguage'] = \count($languages) > 1 && \count($row['langLabels']) === 1;
            $row['summary']    = $this->summary($row);
            $row['displayIcon'] = $isSlider
                ? 'fa-sliders-h'
                : ($this->effectiveDisplay($row) === JtlSearchConfigRepository::DISPLAY_BUTTONS ? 'fa-th-large' : 'fa-list-ul');
            $row['search']     = \mb_strtolower(\implode(' ', [
                $row['name'],
                $row['jtlTitle'],
                ...\array_column($row['shopTitles'], 'value'),
            ]));
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function effectiveDisplay(array $row): string
    {
        if ($row['display'] !== JtlSearchConfigRepository::DISPLAY_AUTO) {
            return $row['display'];
        }

        return $row['jtlType'] === 4 ? JtlSearchConfigRepository::DISPLAY_BUTTONS : JtlSearchConfigRepository::DISPLAY_LIST;
    }

    /**
     * Short description for the overview, e.g. "Regler · Schritt 1 · cm" or "Boxen".
     *
     * @param array<string, mixed> $row
     */
    private function summary(array $row): string
    {
        if ($row['isSlider']) {
            $parts   = ['Regler'];
            $parts[] = $row['step'] > 0
                ? 'Schritt ' . \str_replace('.', ',', \rtrim(\rtrim(\number_format($row['step'], 3, '.', ''), '0'), '.'))
                : 'Schritt wie JTL';
            if ($row['unit'] !== '') {
                $parts[] = $row['unit'];
            }

            return \implode(' · ', $parts);
        }
        $label = self::DISPLAY_LABELS[$this->effectiveDisplay($row)];

        return $row['display'] === JtlSearchConfigRepository::DISPLAY_AUTO ? $label . ' (wie JTL Search)' : $label;
    }

    private function shopURL(): string
    {
        try {
            return Shop::getURL();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Shop languages, default language first: [['iso' => 'ger', 'label' => 'DE', 'name' => 'Deutsch', 'default' => true]].
     *
     * @return array<int, array{iso: string, label: string, name: string, default: bool}>
     */
    private function loadLanguages(): array
    {
        $result = [];
        try {
            $rows = $this->db?->getObjects(
                "SELECT cISO, cNameDeutsch, cShopStandard FROM tsprache ORDER BY cShopStandard = 'Y' DESC, kSprache"
            ) ?? [];
        } catch (\Throwable) {
            $rows = [];
        }
        foreach ($rows as $row) {
            $iso = (string)$row->cISO;
            if (!\preg_match('/^[a-z]{2,3}$/', $iso)) {
                continue;
            }
            $result[] = [
                'iso'     => $iso,
                'label'   => \strtoupper(self::SHORT[$iso] ?? $iso),
                'name'    => (string)$row->cNameDeutsch,
                'default' => (string)$row->cShopStandard === 'Y',
            ];
        }
        if (\count($result) === 0) {
            $result[] = ['iso' => 'ger', 'label' => 'DE', 'name' => 'Deutsch', 'default' => true];
        }

        return $result;
    }

    /**
     * @param array<int, array{iso: string, label: string, name: string, default: bool}> $languages
     * @return array{type: string, text: string}|null
     */
    private function handlePost(array $languages): ?array
    {
        // JTL renders every plugin tab on each request – only react to this tab's own actions
        $action = (string)Request::postVar('mrf_action', '');
        if (!\in_array($action, self::ACTIONS, true)) {
            return null;
        }
        if (!($this->tokenValidator)()) {
            return ['type' => 'danger', 'text' => 'Ungültiges Sicherheits-Token. Bitte die Seite neu laden und erneut versuchen.'];
        }
        $known = $this->repository->getAll();
        if ($action === 'jtl_reorder') {
            $this->repository->reorder(\array_map('\strval', (array)Request::postVar('mrf_jtl_order', [])));

            return ['type' => 'success', 'text' => 'Reihenfolge gespeichert.'];
        }
        $name  = (string)Request::postVar('mrf_jtl_name', '');
        $group = $known[$name] ?? null;
        if ($group === null) {
            return ['type' => 'danger', 'text' => 'Diese Filtergruppe ist nicht (mehr) in der Liste.'];
        }
        $label = $group['title'] !== '' ? $group['title'] : ($group['jtlTitle'] !== '' ? $group['jtlTitle'] : $name);
        switch ($action) {
            case 'jtl_toggle':
                $active = Request::postInt('mrf_active', 0) === 1;
                $this->repository->setActive($name, $active);

                return ['type' => 'success', 'text' => '„' . $label . '“ ' . ($active ? 'wird im Shop angezeigt.' : 'ausgeblendet.')];
            case 'jtl_forget':
                $this->repository->remove($name);

                return ['type' => 'success', 'text' => '„' . $label . '“ aus der Liste entfernt. Liefert JTL Search die Gruppe wieder, erscheint sie erneut.'];
            case 'jtl_edit':
                $data   = Request::postVar('mrf', []);
                $data   = \is_array($data) ? $data : [];
                $titles = [];
                $title  = '';
                foreach ($languages as $language) {
                    $text = (string)($data['titles'][$language['iso']] ?? '');
                    if ($language['default']) {
                        $title = $text;
                    } else {
                        $titles[$language['iso']] = $text;
                    }
                }
                $row = JtlSearchConfigRepository::normalize([
                    'name'     => $name,
                    'title'    => $title,
                    'titles'   => $titles,
                    'display'  => $data['display'] ?? JtlSearchConfigRepository::DISPLAY_AUTO,
                    'unit'     => $data['unit'] ?? '',
                    'step'     => $data['step'] ?? 0,
                    'expanded' => isset($data['expanded']) ? 1 : 0,
                ]);
                $this->repository->saveOne($row);
                $saved = $row['title'] !== '' ? $row['title'] : $label;

                return ['type' => 'success', 'text' => '„' . $saved . '“ gespeichert. Auf bereits zwischengespeicherten Suchseiten wirkt es erst nach dem Leeren des LiteSpeed-Caches.'];
        }

        return null;
    }
}
