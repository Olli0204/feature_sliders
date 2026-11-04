{* Filter-Dialog auf der Suchseite mit JTL Search: NOVA listet hier nur getAvailableContentFilters(), die der Core
   bei JTL Search leer lässt. Die JTL-Search-Gruppen ($mrfJtl.options, CharacteristicOption) kommen deshalb hier
   dazu – mit genau dem Markup aus NOVA snippets/filter/mobile.tpl (Block snippets-filter-mobile-filters). *}
{if isset($mrfAssets)}{include file=$mrfAssets.tpl}{/if}
{foreach $mrfJtl.options as $subFilter}
    {assign var=visible value=$subFilter->isActive() || $Einstellungen.template.productlist.filter_items_always_visible === 'Y'}
    {assign var=mrfKey value=(int)$subFilter->getValue()}
    <li class="snippets-filter-mobile-item">
        {link class="collapsed"
            data=["toggle"=> "collapse", "target"=>"#filter-collapse-{$subFilter->getFrontendName()|seofy}"]
            aria=["expanded"=>($visible===true) ? "true" : "false"]
            rel="nofollow"}
            <span class="characteristic-collapse-btn-inner text-truncate">
                {$subFilter->getFrontendName()|escape:'html'}
            </span>
        {/link}
        {collapse id="filter-collapse-{$subFilter->getFrontendName()|seofy}"
            class="snippets-filter-mobile-item-collapse"
            visible=$visible}
            {if isset($mrfSliders) && isset($mrfSliders[$mrfKey])}
                {include file=$mrfSliders[$mrfKey].tpl mrfSlider=$mrfSliders[$mrfKey]}
            {elseif isset($mrfButtons) && isset($mrfButtons[$mrfKey])}
                {include file=$mrfButtons[$mrfKey].tpl Merkmal=$subFilter mrfColumns=$mrfButtons[$mrfKey].columns}
            {else}
                {include file='snippets/filter/characteristic.tpl' Merkmal=$subFilter sub=true}
            {/if}
        {/collapse}
    </li>
{/foreach}
