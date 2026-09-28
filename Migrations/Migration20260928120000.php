<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * 1.2.0: Darstellung je Merkmal statt eines einzelnen Merkmals in den Plugin-Einstellungen.
 * Übernimmt die bisherige Konfiguration (mrf_characteristic & Co.) aus der alten Plugin-Version –
 * JTL führt Migrationen aus, bevor die Einstellungen der alten Version entfernt werden.
 */
class Migration20260928120000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `feature_sliders_characteristic` (
                `kMerkmal` INT UNSIGNED  NOT NULL,
                `display`  VARCHAR(20)   NOT NULL DEFAULT "slider_range" COMMENT "slider_range | slider_single",
                `mode`     VARCHAR(10)   NOT NULL DEFAULT "overlap"      COMMENT "overlap | contains | within",
                `unit`     VARCHAR(20)   NOT NULL DEFAULT ""             COMMENT "leer = aus den Werten ermitteln",
                `step`     DECIMAL(10,3) NOT NULL DEFAULT 1,
                `expanded` TINYINT(1)    NOT NULL DEFAULT 1,
                PRIMARY KEY (`kMerkmal`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $old = [];
        foreach (
            $this->fetchAll(
                "SELECT e.cName, e.cWert
                    FROM tplugineinstellungen AS e
                    JOIN tplugin AS p ON p.kPlugin = e.kPlugin
                    WHERE p.cPluginID = 'feature_sliders'
                        AND e.cName IN ('mrf_characteristic', 'mrf_mode', 'mrf_unit', 'mrf_step', 'mrf_expanded')
                    ORDER BY e.kPlugin"
            ) as $row
        ) {
            $old[$row->cName] ??= (string)$row->cWert;
        }
        $characteristicID = (int)($old['mrf_characteristic'] ?? 0);
        if ($characteristicID <= 0) {
            return;
        }
        $mode = \in_array($old['mrf_mode'] ?? '', ['overlap', 'contains', 'within'], true) ? $old['mrf_mode'] : 'overlap';
        $step = (float)\str_replace(',', '.', $old['mrf_step'] ?? '1');
        $this->getDB()->queryPrepared(
            'INSERT IGNORE INTO `feature_sliders_characteristic` (kMerkmal, display, mode, unit, step, expanded)
                VALUES (:id, "slider_range", :mode, :unit, :step, :expanded)',
            [
                'id'       => $characteristicID,
                'mode'     => $mode,
                'unit'     => \mb_substr(\trim($old['mrf_unit'] ?? ''), 0, 20),
                'step'     => $step > 0 ? $step : 1,
                // mrf_expanded existiert erst seit 1.1.0; vorher war der Regler immer offen
                'expanded' => ($old['mrf_expanded'] ?? 'on') === 'on' ? 1 : 0,
            ]
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `feature_sliders_characteristic`');
        }
    }
}
