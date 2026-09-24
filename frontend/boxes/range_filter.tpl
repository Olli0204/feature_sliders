{* Sidebar-Box (Boxenverwaltung → Artikelliste → linke Seitenleiste). Markup wie NOVA box_filter_pricerange.tpl. *}
{if isset($mrfSlider)
    && $nSeitenTyp === $smarty.const.PAGE_ARTIKELLISTE
    && !($isMobile || $Einstellungen.template.productlist.filter_placement === 'modal')}
    <div id="sidebox{$oBox->getID()}" class="box box-filter-price box-filter-mrf d-none d-lg-block">
        <button type="button"
                class="btn btn-link btn-block btn-filter-box dropdown-toggle"
                data-toggle="collapse"
                data-target="#cllps-box{$oBox->getID()}"
                aria-expanded="true">
            <span class="text-truncate">{$mrfSlider.title}</span>
        </button>
        <div class="collapse show box-filter-price-collapse" id="cllps-box{$oBox->getID()}">
            {include file=$mrfSlider.tpl}
        </div>
        <hr class="box-filter-hr">
    </div>
{/if}
