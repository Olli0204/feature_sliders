{* Filter-Dialog (mobil bzw. Filterplatzierung "modal"): unter jedem als Schieberegler bzw. Boxen konfigurierten Merkmal
   stehen Regler bzw. Boxen statt der Checkbox-Werte. Der Aufklapp-Link (Block ...-button) bleibt NOVA-Original. *}
{block name='snippets-filter-mobile-filters-collapse'}
    {assign var=mrfKey value=(int)$subFilter->getValue()}
    {if isset($mrfSliders) && isset($mrfSliders[$mrfKey])}
        {if isset($mrfAssets)}{include file=$mrfAssets.tpl}{/if}
        {collapse id="filter-collapse-{$subFilter->getFrontendName()|seofy}"
            class="snippets-filter-mobile-item-collapse"
            visible=$visible}
            {include file=$mrfSliders[$mrfKey].tpl mrfSlider=$mrfSliders[$mrfKey]}
        {/collapse}
    {elseif isset($mrfButtons) && isset($mrfButtons[$mrfKey])}
        {if isset($mrfAssets)}{include file=$mrfAssets.tpl}{/if}
        {collapse id="filter-collapse-{$subFilter->getFrontendName()|seofy}"
            class="snippets-filter-mobile-item-collapse"
            visible=$visible}
            {include file=$mrfButtons[$mrfKey].tpl Merkmal=$subFilter mrfColumns=$mrfButtons[$mrfKey].columns}
        {/collapse}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
