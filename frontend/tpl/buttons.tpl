{* Merkmalwerte als Boxen statt Checkliste. Links, URLs, Mehrfachauswahl und Aktiv-Status kommen unverändert aus dem
   JTL-Merkmalfilter ($Merkmal = CharacteristicOption); wie NOVA snippets/filter/characteristic.tpl mit {link},
   PRG-Pattern und der Klasse "filter-item" (im Filter-Dialog lädt NOVA darüber per AJAX nach).
   Trefferanzahl nach der Shop-Einstellung "Merkmalfilter: Trefferanzahl anzeigen". *}
{assign var=mrfShowCount value=($Einstellungen.navigationsfilter.merkmalfilter_trefferanzahl_anzeigen !== 'N'
    && !($Einstellungen.navigationsfilter.merkmalfilter_trefferanzahl_anzeigen === 'E' && $Merkmal->getData('isMultiSelect')))}
<div class="mrf-buttons" role="group" aria-label="{$Merkmal->getName()|escape:'html'}">
    {foreach $Merkmal->getOptions() as $attributeValue}
        {link class="mrf-button filter-item{if $attributeValue->isActive()} active{/if}"
            usePRG=($Einstellungen.prgpattern.prg_pattern_enabled !== 'N')
            href="{if !empty($attributeValue->getURL())}{$attributeValue->getURL()}{else}#{/if}"
            title="{$attributeValue->getValue()|escape:'html'}{if $mrfShowCount}: {$attributeValue->getCount()}{/if}"
            aria=["pressed" => ($attributeValue->isActive()) ? "true" : "false"]
            rel="nofollow"}
            <span class="mrf-button-label">{$attributeValue->getValue()|escape:'html'}</span>
            {if $mrfShowCount}
                <span class="mrf-button-count">{$attributeValue->getCount()}<span class="sr-only"> {lang key='productsFound'}</span></span>
            {/if}
        {/link}
    {/foreach}
</div>
