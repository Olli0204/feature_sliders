{* Filter-Dialog (mobil bzw. Filterplatzierung "modal"): unter dem Merkmal des Plugins steht der Schieberegler
   statt der Checkbox-Werte. Der Aufklapp-Link (Block ...-button) bleibt NOVA-Original. *}
{block name='snippets-filter-mobile-filters-collapse'}
    {if isset($mrfSlider) && (int)$subFilter->getValue() === $mrfSlider.characteristicID}
        {collapse id="filter-collapse-{$subFilter->getFrontendName()|seofy}"
            class="snippets-filter-mobile-item-collapse"
            visible=$visible}
            {include file=$mrfSlider.tpl}
        {/collapse}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
