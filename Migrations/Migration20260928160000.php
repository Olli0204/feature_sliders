<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * 1.4.0: Filter einzeln deaktivierbar (Einstellungen bleiben erhalten, im Shop erscheint der normale
 * Merkmalfilter) und feste Spaltenzahl für die Boxen-Darstellung (0 = automatisch).
 */
class Migration20260928160000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'ALTER TABLE `feature_sliders_characteristic`
                ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `kMerkmal`,
                ADD COLUMN `button_columns` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT "0 = automatisch" AFTER `expanded`'
        );
    }

    public function down(): void
    {
        $this->execute(
            'ALTER TABLE `feature_sliders_characteristic` DROP COLUMN `active`, DROP COLUMN `button_columns`'
        );
    }
}
