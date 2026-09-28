{* Merkmalwerte als Boxen statt Checkliste: gleich breite Kacheln im Raster, ohne Trefferanzahl.
   Links, URLs, Mehrfachauswahl und Aktiv-Status kommen unverändert aus dem JTL-Merkmalfilter
   ($Merkmal = CharacteristicOption); wie NOVA snippets/filter/characteristic.tpl mit {link}, PRG-Pattern und der
   Klasse "filter-item" (im Filter-Dialog lädt NOVA darüber per AJAX nach).
   $mrfColumns (1–4) berechnet Bootstrap::buttonColumns() aus dem längsten Wert. *}
<div class="mrf-buttons mrf-buttons-cols-{$mrfColumns|default:3}" role="group" aria-label="{$Merkmal->getName()|escape:'html'}">
    {foreach $Merkmal->getOptions() as $attributeValue}
        {link class="mrf-button filter-item{if $attributeValue->isActive()} active{/if}"
            usePRG=($Einstellungen.prgpattern.prg_pattern_enabled !== 'N')
            href="{if !empty($attributeValue->getURL())}{$attributeValue->getURL()}{else}#{/if}"
            title="{$attributeValue->getValue()|escape:'html'}"
            aria=["pressed" => ($attributeValue->isActive()) ? "true" : "false"]
            rel="nofollow"}
            {$attributeValue->getValue()|escape:'html'}
        {/link}
    {/foreach}
</div>
