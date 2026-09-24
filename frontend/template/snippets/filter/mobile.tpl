{* Filter-Modal (mobil bzw. Filterplatzierung "modal"): Schieberegler statt der generischen Optionsliste. *}
{block name='snippets-filter-mobile-filters-generic'}
    {if isset($mrfSlider) && $filter->getClassName() === $mrfSlider.className}
        {link class="snippets-filter-mobile-mrf-toggle"
            data=["toggle"=> "collapse", "target"=>"#filter-collapse-{$mrfSlider.id}"]
            aria=["expanded"=>"true"]
            rel="nofollow"}
            <span class="text-truncate">{$mrfSlider.title}</span>
        {/link}
        {collapse id="filter-collapse-{$mrfSlider.id}"
            class="snippets-filter-mobile-item-collapse"
            visible=true}
            {include file=$mrfSlider.tpl}
        {/collapse}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
