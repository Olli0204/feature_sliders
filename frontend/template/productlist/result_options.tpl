{* Suchseite mit JTL Search: Der Core meldet dort keine Inhaltsfilter (getAvailableContentFilters() ist leer),
   NOVA zeigt deshalb keinen Filter-Knopf. Liefert feature_sliders die JTL-Search-Filter ($mrfJtl, siehe
   Filter/JtlSearchFilters.php), wird der Knopf samt Filter-Dialog trotzdem ausgegeben. Markup bleibt NOVA-Original. *}
{block name='productlist-result-options-filter-link'}
    {if isset($mrfJtl) && !$show_filters}
        {assign var=show_filters value=true}
    {/if}
    {$smarty.block.parent}
{/block}
