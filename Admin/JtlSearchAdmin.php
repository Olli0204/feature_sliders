<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Admin;

use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Plugin\PluginInterface;
use JTL\Smarty\JTLSmarty;
use Plugin\feature_sliders\Filter\JtlSearchConfigRepository;
use Plugin\feature_sliders\Filter\Settings;

/**
 * Backend tab "JTL Search": per filter group of the JTL Search plugin (search page) title, display, unit, step,
 * order, expanded state and visibility. The groups are recorded by the shop frontend (JtlSearchFilters).
 */
final class JtlSearchAdmin
{
    /** POST actions of this tab (mrf_action) */
    public const ACTIONS = ['jtl_save', 'jtl_forget'];

    public const TYPE_LABELS = [1 => 'Checkbox', 2 => 'Regler', 3 => 'Farbbox', 4 => 'Tinybox'];

    public const DISPLAY_LABELS = [
        JtlSearchConfigRepository::DISPLAY_AUTO    => 'wie JTL Search',
        JtlSearchConfigRepository::DISPLAY_LIST    => 'Checkliste',
        JtlSearchConfigRepository::DISPLAY_BUTTONS => 'Boxen',
    ];

    /** @var \Closure(): bool */
    private readonly \Closure $tokenValidator;

    public function __construct(
        private readonly PluginInterface $plugin,
        private readonly JtlSearchConfigRepository $repository,
        ?\Closure $tokenValidator = null
    ) {
        $this->tokenValidator = $tokenValidator ?? static fn(): bool => Form::validateToken();
    }

    public function render(int $menuID, JTLSmarty $smarty): string
    {
        $message = $this->handlePost();
        $rows    = \array_values($this->repository->getAll());
        $today   = new \DateTimeImmutable('today');
        foreach ($rows as &$row) {
            $row['isSlider'] = $row['jtlType'] === JtlSearchConfigRepository::TYPE_SLIDER;
            $row['typeLabel'] = self::TYPE_LABELS[$row['jtlType']] ?? (string)$row['jtlType'];
            $row['daysAgo']  = $row['lastSeen'] === null
                ? null
                : (int)$today->diff(new \DateTimeImmutable($row['lastSeen']))->days;
        }
        unset($row);
        $settings = Settings::fromPlugin($this->plugin);

        return $smarty->assign('mrfJtlRows', $rows)
            ->assign('mrfMenuID', $menuID)
            ->assign('mrfMessage', $message)
            ->assign('mrfJtlEnabled', $settings->active && $settings->jtlSearchFilters)
            ->assign('mrfJtlDisplayLabels', self::DISPLAY_LABELS)
            ->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/jtlsearch.tpl');
    }

    /**
     * @return array{type: string, text: string}|null
     */
    private function handlePost(): ?array
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
        if ($action === 'jtl_forget') {
            $name = (string)Request::postVar('mrf_jtl_name', '');
            if (!isset($known[$name])) {
                return null;
            }
            $this->repository->remove($name);

            return ['type' => 'success', 'text' => '„' . ($known[$name]['jtlTitle'] ?: $name) . '“ aus der Liste entfernt. Liefert JTL Search die Gruppe wieder, erscheint sie erneut.'];
        }
        $posted = Request::postVar('mrf_jtl', []);
        $rows   = [];
        foreach (\is_array($posted) ? $posted : [] as $name => $data) {
            $name = (string)$name;
            if (!isset($known[$name]) || !\is_array($data)) {
                continue;
            }
            // unchecked checkboxes are missing from the POST data
            $rows[] = JtlSearchConfigRepository::normalize([
                'name'     => $name,
                'active'   => isset($data['active']) ? 1 : 0,
                'title'    => $data['title'] ?? '',
                'display'  => $data['display'] ?? JtlSearchConfigRepository::DISPLAY_AUTO,
                'unit'     => $data['unit'] ?? '',
                'step'     => $data['step'] ?? 0,
                'expanded' => isset($data['expanded']) ? 1 : 0,
            ]);
        }
        $this->repository->saveAll($rows);

        return ['type' => 'success', 'text' => 'Einstellungen gespeichert. Auf bereits zwischengespeicherten Suchseiten wirken sie erst nach dem Leeren des LiteSpeed-Caches.'];
    }
}
