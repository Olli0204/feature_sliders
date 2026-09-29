<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * 1.5.0: Filtergruppen – welche Merkmale (in welcher Reihenfolge) in einer Kategorie als Filter erscheinen.
 * Ersetzt das Wawi-Funktionsattribut "merkmalfilter" für zugewiesene Kategorien.
 */
class Migration20260929120000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `feature_sliders_group` (
                `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `sort` INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `feature_sliders_group_characteristic` (
                `group_id` INT UNSIGNED NOT NULL,
                `kMerkmal` INT UNSIGNED NOT NULL,
                `sort`     INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (`group_id`, `kMerkmal`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `feature_sliders_group_category` (
                `group_id`   INT UNSIGNED NOT NULL,
                `kKategorie` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`group_id`, `kKategorie`),
                KEY `idx_category` (`kKategorie`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `feature_sliders_group_category`');
            $this->execute('DROP TABLE IF EXISTS `feature_sliders_group_characteristic`');
            $this->execute('DROP TABLE IF EXISTS `feature_sliders_group`');
        }
    }
}
