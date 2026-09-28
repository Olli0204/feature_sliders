{* Desktop-Seitenleiste: im Merkmalfilter wird unter jedem als Schieberegler bzw. Boxen konfigurierten Merkmal statt der
   Checkbox-Werte der Regler bzw. die Boxen ausgegeben. Titel, Aufklapp-Button und Trennlinie bleiben NOVA-Original. *}
{block name='boxes-box-filter-characteristics-characteristics'}
    {assign var=mrfKey value=(int)$characteristic->getValue()}
    {if isset($mrfSliders) && isset($mrfSliders[$mrfKey])}
        {if isset($mrfAssets)}{include file=$mrfAssets.tpl}{/if}
        <div class="mrf-box-content">
            {include file=$mrfSliders[$mrfKey].tpl mrfSlider=$mrfSliders[$mrfKey]}
        </div>
    {elseif isset($mrfButtons) && isset($mrfButtons[$mrfKey])}
        {if isset($mrfAssets)}{include file=$mrfAssets.tpl}{/if}
        {include file=$mrfButtons[$mrfKey].tpl Merkmal=$characteristic mrfColumns=$mrfButtons[$mrfKey].columns}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
