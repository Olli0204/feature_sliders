<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * 1.7.0: Einstellungen je JTL-Search-Filtergruppe (Suchseite). Die Gruppen trägt das Plugin selbst ein,
 * sobald JTL Search sie im Shop liefert (jtl_name, jtl_title, jtl_type = Angaben von JTL Search).
 */
class Migration20261104120000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `feature_sliders_jtlsearch` (
                `jtl_name`  VARCHAR(100)  NOT NULL,
                `jtl_title` VARCHAR(255)  NOT NULL DEFAULT \'\',
                `jtl_type`  TINYINT       NOT NULL DEFAULT 1,
                `active`    TINYINT(1)    NOT NULL DEFAULT 1,
                `title`     VARCHAR(100)  NOT NULL DEFAULT \'\',
                `display`   VARCHAR(10)   NOT NULL DEFAULT \'auto\',
                `unit`      VARCHAR(20)   NOT NULL DEFAULT \'\',
                `step`      DECIMAL(10,3) NOT NULL DEFAULT 0,
                `expanded`  TINYINT(1)    NOT NULL DEFAULT 0,
                `sort`      INT           NOT NULL DEFAULT 0,
                `last_seen` DATE          NULL,
                PRIMARY KEY (`jtl_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `feature_sliders_jtlsearch`');
        }
    }
}
