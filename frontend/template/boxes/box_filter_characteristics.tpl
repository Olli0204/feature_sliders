{* Desktop-Seitenleiste: im Merkmalfilter wird unter dem Merkmal des Plugins statt der Checkbox-Werte der
   Schieberegler ausgegeben. Titel, Aufklapp-Button und Trennlinie bleiben NOVA-Original. *}
{block name='boxes-box-filter-characteristics-characteristics'}
    {if isset($mrfSlider) && (int)$characteristic->getValue() === $mrfSlider.characteristicID}
        <div class="mrf-box-content">
            {include file=$mrfSlider.tpl}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
