<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * 1.8.0: Standard-Reihenfolge der Merkmalfilter auf Kategorieseiten (gilt auch in Kategorien mit Filtergruppe;
 * die Gruppe legt dann nur fest, welche Merkmale erscheinen). Merkmale ohne Eintrag folgen in Wawi-Reihenfolge.
 */
class Migration20261117120000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `feature_sliders_order` (
                `kMerkmal` INT UNSIGNED NOT NULL,
                `sort`     INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (`kMerkmal`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `feature_sliders_order`');
        }
    }
}
