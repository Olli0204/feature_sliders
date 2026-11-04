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

{* Suchseite mit JTL Search: dessen Filtergruppen unter der Sortierung einfügen (NOVA zeigt dort sonst nichts). *}
{block name='snippets-filter-mobile-sorting'}
    {$smarty.block.parent}
    {if isset($mrfJtl)}
        {include file=$mrfJtl.mobileTpl}
    {/if}
{/block}

{* Suchseite mit JTL Search: „x Artikel anzeigen“ führt zur Trefferliste mit den gesetzten Filtern
   (NOVA verlinkt $NaviFilter->getFilterURL(), die die JTL-Search-Parameter fq0, fq1, … nicht kennt). *}
{block name='snippets-filter-mobile-footer-buttons'}
    {if isset($mrfJtl)}
        <div class="productlist-filter-footer">
            {formrow class="snippets-filter-mobile-buttons"}
                {col}
                    {button block=true
                        variant="outline-primary"
                        class="no-caret"
                        data=['toggle'=>'collapse', 'dismiss'=>'modal']
                        href="#collapseFilter"
                        aria=['expanded'=>'true','controls'=>'collapseFilter']}
                        {lang key='filterCancel'}
                    {/button}
                {/col}
                {col}
                    {button type="link"
                        block=true
                        variant="primary"
                        id="submitMobileFilters"
                        class="min-w-sm text-nowrap-util"
                        href=$mrfJtl.url}
                        {lang key='filterShowItem' printf=$itemCount}
                    {/button}
                {/col}
            {/formrow}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
