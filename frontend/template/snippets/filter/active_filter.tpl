{* Suchseite mit JTL Search: Die gesetzten Filter kennt der Core nicht ($NaviFilter->getFilterCount() ist 0) –
   feature_sliders liefert sie in $mrfJtl.active (Name + URL ohne diesen Filter). Markup wie NOVA active_filter.tpl. *}
{block name='snippets-filter-active-filter'}
    {if isset($mrfJtl) && $mrfJtl.active|count > 0}
        <div class="active-filters">
            {foreach $mrfJtl.active as $mrfActive}
                {link href=$mrfActive.url
                    rel="nofollow"
                    title="{lang key='deleteFilter'}"
                    class="btn btn-outline-secondary btn-sm filter-type-Characteristic snippets-filter-item js-filter-item"}
                    {$mrfActive.label|escape:'html'}
                    <span class="fa fa-times snippets-filter-item-icon-left"></span>
                {/link}
            {/foreach}
            {if !empty($mrfJtl.removeAll)}
                {link href=$mrfJtl.removeAll
                    title="{lang key='removeFilters'}"
                    class='snippets-filter-item-all js-filter-item'}
                    {lang key='removeFilters'}
                {/link}
            {/if}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
