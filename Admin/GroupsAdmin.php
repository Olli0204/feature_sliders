<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Admin;

use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Plugin\PluginInterface;
use JTL\Smarty\JTLSmarty;
use Plugin\feature_sliders\Filter\ConfigRepository;
use Plugin\feature_sliders\Filter\GroupRepository;

/**
 * Backend tab "Filtergruppen": groups of characteristics (ordered) assigned to categories.
 */
final class GroupsAdmin
{
    /** @var \Closure(): bool */
    private readonly \Closure $tokenValidator;

    public function __construct(
        private readonly PluginInterface $plugin,
        private readonly DbInterface $db,
        private readonly GroupRepository $groups,
        ?\Closure $tokenValidator = null
    ) {
        $this->tokenValidator = $tokenValidator ?? static fn(): bool => Form::validateToken();
    }

    public function render(int $menuID, JTLSmarty $smarty): string
    {
        $characteristics = $this->loadCharacteristics();
        $categories      = $this->loadCategories();
        [$message, $openEdit] = $this->handlePost($characteristics, $categories);
        $groups          = $this->groups->getAll();
        $rows            = [];
        $assignedTo      = [];
        foreach ($groups as $group) {
            foreach ($group['categories'] as $categoryID) {
                $assignedTo[$categoryID][] = ['id' => $group['id'], 'name' => $group['name']];
            }
        }
        foreach ($groups as $group) {
            $paths  = \array_values(\array_filter(\array_map(
                static fn(int $id): ?string => $categories[$id]['path'] ?? null,
                $group['categories']
            )));
            $rows[] = $group + [
                'characteristicNames' => \array_values(\array_filter(\array_map(
                    static fn(int $id): ?string => $characteristics[$id]['name'] ?? null,
                    $group['characteristics']
                ))),
                'categoryPaths'       => $paths,
                'moreCategories'      => \max(0, \count($paths) - 3),
            ];
        }
        $wawi = \array_values(\array_filter($categories, static fn(array $c): bool => $c['wawiAttribute'] !== ''));
        foreach ($wawi as &$category) {
            $category['hasGroup'] = isset($assignedTo[$category['id']]);
        }
        unset($category);

        return $smarty->assign('mrfGroups', $rows)
            ->assign('mrfCharacteristics', $characteristics)
            ->assign('mrfCategories', $categories)
            ->assign('mrfAssignedTo', $assignedTo)
            ->assign('mrfWawiCategories', $wawi)
            ->assign('mrfMenuID', $menuID)
            ->assign('mrfMessage', $message)
            ->assign('mrfOpenEdit', $openEdit)
            ->assign('mrfGroupFormTpl', $this->plugin->getPaths()->getAdminPath() . 'templates/group_form.tpl')
            ->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/groups.tpl');
    }

    /**
     * @param array<int, array<string, mixed>> $characteristics
     * @param array<int, array<string, mixed>> $categories
     * @return array{0: array{type: string, text: string}|null, 1: int}
     */
    private function handlePost(array $characteristics, array $categories): array
    {
        $action = (string)Request::postVar('mrf_action', '');
        if ($action === '') {
            return [null, 0];
        }
        if (!($this->tokenValidator)()) {
            return [['type' => 'danger', 'text' => 'Ungültiges Sicherheits-Token. Bitte die Seite neu laden und erneut versuchen.'], 0];
        }
        switch ($action) {
            case 'group_save':
                $id   = (int)Request::postInt('mrf_group_id', 0);
                $name = \trim((string)Request::postVar('mrf_group_name', ''));
                if ($name === '') {
                    return [['type' => 'danger', 'text' => 'Bitte einen Namen für die Gruppe angeben.'], $id];
                }
                $selected = \array_values(\array_filter(
                    \array_map('\intval', (array)Request::postVar('mrf_group_characteristics', [])),
                    static fn(int $cid): bool => isset($characteristics[$cid])
                ));
                $assigned = \array_values(\array_filter(
                    \array_map('\intval', (array)Request::postVar('mrf_group_categories', [])),
                    static fn(int $cid): bool => isset($categories[$cid])
                ));
                $isNew = $id <= 0 || $this->groups->get($id) === null;
                $this->groups->save($isNew ? 0 : $id, $name, $selected, $assigned);

                return [['type' => 'success', 'text' => 'Filtergruppe „' . $name . '“ ' . ($isNew ? 'angelegt.' : 'gespeichert.')], 0];
            case 'group_remove':
                $group = $this->groups->get((int)Request::postInt('mrf_group_id', 0));
                if ($group === null) {
                    return [null, 0];
                }
                $this->groups->remove($group['id']);

                return [['type' => 'success', 'text' => 'Filtergruppe „' . $group['name'] . '“ gelöscht.'], 0];
            case 'group_reorder':
                $this->groups->reorder(\array_map('\intval', (array)Request::postVar('mrf_group_order', [])));

                return [['type' => 'success', 'text' => 'Reihenfolge der Gruppen gespeichert.'], 0];
        }

        return [null, 0];
    }

    /**
     * @return array<int, array{id: int, name: string, display: string}> sorted by name
     */
    private function loadCharacteristics(): array
    {
        $configs = (new ConfigRepository($this->db))->getAll();
        $result  = [];
        foreach ($this->db->getObjects('SELECT kMerkmal, cName FROM tmerkmal ORDER BY cName, kMerkmal') as $row) {
            $id          = (int)$row->kMerkmal;
            $config      = $configs[$id] ?? null;
            $result[$id] = [
                'id'      => $id,
                'name'    => (string)$row->cName,
                'display' => $config === null || !$config->active
                    ? ''
                    : ($config->isButtons() ? 'Boxen' : 'Regler'),
            ];
        }

        return $result;
    }

    /**
     * Category tree in shop order with full path and the Wawi attribute "merkmalfilter" (if any).
     *
     * @return array<int, array{id: int, name: string, path: string, level: int, wawiAttribute: string}>
     */
    private function loadCategories(): array
    {
        $rows  = $this->db->getObjects(
            'SELECT kKategorie, kOberKategorie, cName FROM tkategorie ORDER BY lft, nSort, cName'
        );
        $names = [];
        $parent = [];
        foreach ($rows as $row) {
            $names[(int)$row->kKategorie]  = (string)$row->cName;
            $parent[(int)$row->kKategorie] = (int)$row->kOberKategorie;
        }
        $wawi = [];
        foreach (
            $this->db->getObjects(
                "SELECT kKategorie, cWert FROM tkategorieattribut WHERE cName = 'merkmalfilter' AND cWert <> ''"
            ) as $row
        ) {
            $wawi[(int)$row->kKategorie] = (string)$row->cWert;
        }
        $result = [];
        foreach ($names as $id => $name) {
            $path  = [$name];
            $level = 0;
            $seen  = [$id => true];
            $up    = $parent[$id] ?? 0;
            while ($up > 0 && isset($names[$up]) && !isset($seen[$up])) {
                \array_unshift($path, $names[$up]);
                $seen[$up] = true;
                $up        = $parent[$up] ?? 0;
                ++$level;
            }
            $result[$id] = [
                'id'            => $id,
                'name'          => $name,
                'path'          => \implode(' › ', $path),
                'level'         => $level,
                'wawiAttribute' => $wawi[$id] ?? '',
            ];
        }

        return $result;
    }
}
