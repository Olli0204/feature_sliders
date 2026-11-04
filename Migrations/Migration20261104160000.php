<?php

declare(strict_types=1);

namespace Plugin\feature_sliders\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * 1.7.1: Titel je Sprache für die JTL-Search-Filtergruppen (titles: JSON {"eng": "Board length"}; `title` bleibt
 * der Titel der Standardsprache) und die Sprachen, in denen JTL Search die Gruppe geliefert hat (langs: "ger,eng").
 */
class Migration20261104160000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `feature_sliders_jtlsearch` LIKE 'titles'") === null) {
            $this->execute('ALTER TABLE `feature_sliders_jtlsearch` ADD COLUMN `titles` TEXT NULL AFTER `title`');
        }
        if ($this->fetchOne("SHOW COLUMNS FROM `feature_sliders_jtlsearch` LIKE 'langs'") === null) {
            $this->execute(
                'ALTER TABLE `feature_sliders_jtlsearch` ADD COLUMN `langs` VARCHAR(50) NOT NULL DEFAULT \'\' AFTER `jtl_type`'
            );
        }
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('ALTER TABLE `feature_sliders_jtlsearch` DROP COLUMN `titles`, DROP COLUMN `langs`');
        }
    }
}
