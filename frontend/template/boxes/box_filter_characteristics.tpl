{* Desktop-Seitenleiste: im Merkmalfilter wird unter jedem als Schieberegler konfigurierten Merkmal statt der
   Checkbox-Werte der Regler ausgegeben. Titel, Aufklapp-Button und Trennlinie bleiben NOVA-Original. *}
{block name='boxes-box-filter-characteristics-characteristics'}
    {assign var=mrfKey value=(int)$characteristic->getValue()}
    {if isset($mrfSliders) && isset($mrfSliders[$mrfKey])}
        <div class="mrf-box-content">
            {include file=$mrfSliders[$mrfKey].tpl mrfSlider=$mrfSliders[$mrfKey]}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
