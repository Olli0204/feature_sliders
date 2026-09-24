<?php

declare(strict_types=1);

use JTL\Shop;

/**
 * Dynamic options for the setting "mrf_characteristic": all characteristics of the shop.
 * Included by JTL\Plugin\Data\Config::getDynamicOptions(), must return stdClass[] with cName/cWert/nSort.
 */
$mrfOptions = [
    (object)['cName' => '– bitte wählen –', 'cWert' => '0', 'nSort' => 0],
];
try {
    $mrfRows = Shop::Container()->getDB()->getObjects(
        'SELECT kMerkmal, cName FROM tmerkmal ORDER BY cName, kMerkmal'
    );
    foreach ($mrfRows as $mrfIdx => $mrfRow) {
        $mrfOptions[] = (object)[
            'cName' => $mrfRow->cName . ' (ID ' . $mrfRow->kMerkmal . ')',
            'cWert' => (string)$mrfRow->kMerkmal,
            'nSort' => $mrfIdx + 1,
        ];
    }
} catch (\Throwable) {
}

return $mrfOptions;
