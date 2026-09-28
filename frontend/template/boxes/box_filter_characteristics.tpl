{* Desktop-Seitenleiste: im Merkmalfilter wird unter jedem als Schieberegler bzw. Boxen konfigurierten Merkmal statt der
   Checkbox-Werte der Regler bzw. die Boxen ausgegeben. Titel, Aufklapp-Button und Trennlinie bleiben NOVA-Original. *}
{block name='boxes-box-filter-characteristics-characteristics'}
    {assign var=mrfKey value=(int)$characteristic->getValue()}
    {if isset($mrfSliders) && isset($mrfSliders[$mrfKey])}
        <div class="mrf-box-content">
            {include file=$mrfSliders[$mrfKey].tpl mrfSlider=$mrfSliders[$mrfKey]}
        </div>
    {elseif isset($mrfButtons) && isset($mrfButtons[$mrfKey])}
        {include file=$mrfButtons[$mrfKey].tpl Merkmal=$characteristic}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
