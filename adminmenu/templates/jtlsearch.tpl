{* Tab „JTL Search“: Filtergruppen der Suchseite (Plugin JTL Search). Aufbau wie der Tab „Merkmale“: kompakte Übersicht
   (Schalter und Reihenfolge wirken sofort), Bearbeiten im Dialog, ausgeblendete und nicht mehr gelieferte Gruppen in
   eigenen, eingeklappten Abschnitten. Bausteine aus dem JTL-Backend (card, table.list, custom-switch, modal). *}
<style>
    .mrf-jtl .mrf-drag-cell { width: 2rem; cursor: grab; opacity: .55; }
    .mrf-jtl tr.mrf-dragging { opacity: .4; }
    .mrf-jtl .mrf-spacer { cursor: default; }
    .mrf-jtl table { table-layout: fixed; }
    .mrf-jtl .mrf-col-title { width: 38%; }
    .mrf-jtl .mrf-col-switch { width: 6.5rem; white-space: nowrap; }
    .mrf-jtl .mrf-col-actions { width: 6rem; white-space: nowrap; }
    .mrf-jtl .mrf-col-lang { width: 9rem; }
    .mrf-jtl td { vertical-align: middle !important; }
    .mrf-jtl .mrf-title { font-weight: 600; }
    .mrf-jtl .mrf-sub { display: block; font-size: .8125rem; opacity: .7; }
    .mrf-jtl .mrf-sub .mrf-missing { font-style: italic; }
    .mrf-jtl .mrf-display .fal { width: 1.25rem; opacity: .8; }
    .mrf-jtl .mrf-chip { display: inline-block; margin-right: .25rem; padding: .05rem .45rem; font-size: .75rem; background: var(--table-accent-color, #f5f7fa); border: 1px solid var(--border-color, #e4e9f2); border-radius: .75rem; white-space: nowrap; }
    .mrf-jtl .mrf-chip-warn { border-color: #f5c26b; background: rgba(245, 194, 107, .15); }
    .mrf-jtl .custom-switch .custom-control-input:checked ~ .custom-control-label::before,
    .mrf-jtl-modal .custom-switch .custom-control-input:checked ~ .custom-control-label::before { background-color: var(--primary, #5cbcf6); border-color: var(--primary, #5cbcf6); }
    .mrf-jtl .mrf-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
    .mrf-jtl .mrf-toolbar .mrf-intro { flex: 1 1 22rem; margin: 0; }
    .mrf-jtl .mrf-toolbar .mrf-tools { display: flex; flex-wrap: wrap; gap: .5rem; }
    .mrf-jtl .mrf-filter-input { width: 14rem; }
    .mrf-jtl .mrf-try { width: 17rem; }
    .mrf-jtl .mrf-help { font-size: .875rem; }
    .mrf-jtl .mrf-help li { margin-bottom: .25rem; }
    .mrf-jtl .mrf-section-toggle { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .75rem 0; border: 0; border-top: 1px solid var(--border-color, #e4e9f2); background: none; color: var(--body-color, #435a6b); font-weight: 600; text-align: left; }
    .mrf-jtl .mrf-section-toggle .fa-chevron-down { transition: transform .2s; }
    .mrf-jtl .mrf-section-toggle[aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }
    .mrf-jtl .mrf-section-toggle .mrf-count { font-weight: 400; opacity: .7; }
    .mrf-jtl .mrf-empty { padding: 2.5rem 1rem; text-align: center; }
    .mrf-jtl .mrf-empty-icon { display: block; margin: 0 auto .75rem; font-size: 2rem; line-height: 1; opacity: .35; }
    .mrf-jtl .mrf-row-off td:not(.mrf-col-switch):not(.mrf-col-actions) { opacity: .5; }
    .mrf-jtl .mrf-nohit { display: none; padding: 1rem; opacity: .7; }
    .mrf-jtl-modal .mrf-section-label { font-weight: 600; margin-bottom: .5rem; }
    .mrf-jtl-modal .mrf-lang { min-width: 2.5rem; justify-content: center; font-size: .75rem; }
    .mrf-jtl-modal .mrf-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: .75rem; }
    .mrf-jtl-modal .mrf-tile { position: relative; display: block; margin: 0; padding: .75rem; border: 1px solid var(--border-color, #e4e9f2); border-radius: .25rem; cursor: pointer; background: var(--card-bg, #fff); color: var(--body-color, #435a6b); font-weight: 400; }
    .mrf-jtl-modal .mrf-tile:hover { border-color: var(--primary, #5cbcf6); }
    .mrf-jtl-modal .mrf-tile input { position: absolute; opacity: 0; pointer-events: none; }
    .mrf-jtl-modal .mrf-tile.is-selected { border-color: var(--primary, #5cbcf6); box-shadow: 0 0 0 1px var(--primary, #5cbcf6); }
    .mrf-jtl-modal .mrf-tile-title { display: block; font-weight: 600; font-size: .875rem; margin-top: .5rem; }
    .mrf-jtl-modal .mrf-tile-text { display: block; font-size: .75rem; opacity: .75; line-height: 1.3; margin-top: .125rem; }
    .mrf-jtl-modal .mrf-art { display: block; height: 2rem; }
    .mrf-jtl-modal .mrf-art .l i { display: flex; align-items: center; gap: .3rem; height: .55rem; margin-bottom: .2rem; }
    .mrf-jtl-modal .mrf-art .l i::before { content: ''; width: .5rem; height: .5rem; border: 1px solid currentColor; border-radius: 1px; opacity: .8; }
    .mrf-jtl-modal .mrf-art .l i::after { content: ''; flex: 0 0 60%; height: 2px; background: currentColor; opacity: .35; }
    .mrf-jtl-modal .mrf-art .l i:nth-child(2)::before { background: #ffa54f; border-color: #ffa54f; }
    .mrf-jtl-modal .mrf-art .b { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; padding-top: .35rem; }
    .mrf-jtl-modal .mrf-art .b i { display: block; height: 1.1rem; border: 1px solid currentColor; border-radius: 2px; opacity: .8; }
    .mrf-jtl-modal .mrf-art .b i:nth-child(2) { background: #ffa54f; border-color: #ffa54f; opacity: 1; }
    .mrf-jtl-modal .mrf-art .j { display: block; padding-top: .35rem; font-size: 1.25rem; opacity: .6; }
    .mrf-jtl-modal .mrf-preview { background: var(--table-accent-color, #f5f7fa); border-radius: .25rem; padding: 1rem; }
    .mrf-jtl-modal .mrf-preview-frame { background: #fff; border: 1px solid #e3e6ea; border-radius: .25rem; padding: .75rem 1rem 1rem; max-width: 300px; margin: 0 auto; color: #525252; font-family: 'Open Sans', sans-serif; }
    .mrf-jtl-modal .mrf-pv-title { font-size: 1rem; margin-bottom: .75rem; }
    .mrf-jtl-modal .mrf-pv-inputs { display: flex; justify-content: space-between; margin-bottom: 1.1rem; }
    .mrf-jtl-modal .mrf-pv-input { display: flex; width: 41.6%; height: 1.6rem; border: 1px solid #707070; border-radius: .125rem; font-weight: 700; font-size: .875rem; overflow: hidden; }
    .mrf-jtl-modal .mrf-pv-input span:first-child { flex: 1; padding: 0 .5rem; line-height: 1.5rem; }
    .mrf-jtl-modal .mrf-pv-input .u { padding: 0 .5rem; line-height: 1.5rem; background: #ebebeb; border-left: 1px solid #707070; }
    .mrf-jtl-modal .mrf-pv-input .u:empty { display: none; }
    .mrf-jtl-modal .mrf-pv-track { position: relative; height: .3em; margin: 0 .5rem .5rem; background: #ffa54f; }
    .mrf-jtl-modal .mrf-pv-track::before, .mrf-jtl-modal .mrf-pv-track::after { content: ''; position: absolute; top: -.2em; width: .7em; height: .7em; border-radius: 50%; background: #ffa54f; box-shadow: 0 0 0 5px rgba(255, 165, 79, .5); }
    .mrf-jtl-modal .mrf-pv-track::before { left: -.35em; }
    .mrf-jtl-modal .mrf-pv-track::after { right: -.35em; }
    .mrf-jtl-modal .mrf-pv-note { font-size: .75rem; color: #6c757d; text-align: center; margin-top: .75rem; }
    .mrf-jtl-modal .mrf-tech { font-size: .8125rem; }
    .mrf-jtl-modal .mrf-tech dt { font-weight: 400; opacity: .7; }
    .mrf-jtl-modal .mrf-tech dd { word-break: break-all; }
</style>

{* ---------- eine Zeile der Übersicht ($row, $mrfDrag = sortierbar) ---------- *}
{function name=mrfJtlRow}
    <tr class="mrf-jtl-row{if !$row.active} mrf-row-off{/if}" data-search="{$row.search|escape:'html'}"{if $mrfDrag} draggable="true"{/if}>
        {if $mrfDrag}
            <td class="mrf-drag-cell" title="Reihenfolge per Ziehen ändern – wird sofort gespeichert">
                <span class="fal fa-grip-vertical"></span>
                <input type="hidden" name="mrf_jtl_order[]" value="{$row.name|escape:'html'}" form="mrf-jtl-order-form">
            </td>
        {else}
            <td class="mrf-drag-cell mrf-spacer"></td>
        {/if}
        <td class="text-center mrf-col-switch">
            <div class="custom-control custom-switch d-inline-block" title="{if $row.active}Im Shop ausblenden{else}Im Shop anzeigen{/if}">
                <input type="checkbox" class="custom-control-input mrf-jtl-toggle" id="mrf-jtl-active-{$row.id}"
                       data-name="{$row.name|escape:'html'}"{if $row.active} checked{/if}>
                <label class="custom-control-label" for="mrf-jtl-active-{$row.id}"><span class="sr-only">Anzeigen</span></label>
            </div>
        </td>
        <td>
            <span class="mrf-title">{$row.mainTitle|escape:'html'}</span>
            {foreach $row.shopTitles as $title}
                {if !$title@first}
                    <span class="mrf-sub">{$title.label}: {if $title.own}{$title.value|escape:'html'}{else}<span class="mrf-missing">wie JTL Search</span>{/if}</span>
                {/if}
            {/foreach}
        </td>
        <td class="mrf-display">
            <span class="fal {$row.displayIcon}"></span> {$row.summary|escape:'html'}
            {if $row.expanded}<span class="mrf-sub">aufgeklappt</span>{/if}
        </td>
        {if $mrfJtlLanguages|count > 1}
            <td class="mrf-col-lang">
                {foreach $row.langLabels as $language}<span class="mrf-chip{if $row.onlyOneLanguage} mrf-chip-warn{/if}" title="von JTL Search in {$language.name|escape:'html'} geliefert">{$language.label}</span>{/foreach}
                {if $row.onlyOneLanguage}<span class="mrf-sub">nur diese Sprache</span>{/if}
            </td>
        {/if}
        <td class="text-right mrf-col-actions">
            <button type="button" class="btn btn-link px-2" data-toggle="modal" data-target="#mrf-jtl-edit-{$row.id}" title="Bearbeiten">
                <span class="icon-hover">
                    <span class="fal fa-edit"></span>
                    <span class="fas fa-edit"></span>
                </span>
            </button>
            {if $row.stale}
                <button type="submit" class="btn btn-link px-2 mrf-jtl-forget" form="mrf-jtl-forget-form" name="mrf_jtl_name"
                        value="{$row.name|escape:'html'}" data-title="{$row.mainTitle|escape:'html'}" title="Aus der Liste entfernen">
                    <span class="icon-hover">
                        <span class="fal fa-trash-alt"></span>
                        <span class="fas fa-trash-alt"></span>
                    </span>
                </button>
            {/if}
        </td>
    </tr>
{/function}

{function name=mrfJtlTable}
    <div class="table-responsive">
        <table class="list table mb-0">
            <thead>
                <tr>
                    <th class="mrf-drag-cell"><span class="sr-only">{if $mrfDrag}Reihenfolge{/if}</span></th>
                    <th class="text-center mrf-col-switch">Anzeigen</th>
                    <th class="mrf-col-title">Titel im Shop</th>
                    <th>Darstellung</th>
                    {if $mrfJtlLanguages|count > 1}<th class="mrf-col-lang">Sprachen</th>{/if}
                    <th class="mrf-col-actions"><span class="sr-only">Aktionen</span></th>
                </tr>
            </thead>
            <tbody{if $mrfDrag} id="mrf-jtl-sortable"{/if}>
                {foreach $mrfRows as $row}
                    {call name=mrfJtlRow row=$row mrfDrag=$mrfDrag}
                {/foreach}
            </tbody>
        </table>
        <div class="mrf-nohit">Keine passende Gruppe.</div>
    </div>
{/function}

<div class="mrf-jtl">
    {if $mrfMessage !== null}
        <div class="alert alert-{$mrfMessage.type}">{$mrfMessage.text|escape:'html'}</div>
    {/if}
    {if !$mrfJtlEnabled}
        <div class="alert alert-warning">
            Unter <strong>Einstellungen</strong> ist „JTL-Search-Filter im Shop-Design“ (oder das ganze Plugin) ausgeschaltet –
            auf der Suchseite zeigt gerade die Box „Filter (JTL Search)“ die Filter, diese Einstellungen wirken nicht.
        </div>
    {/if}

    <div class="card">
        <div class="card-header">
            <div class="subheading1">Filter der Suchseite (JTL Search)</div>
        </div>
        <div class="card-body">
            <div class="mrf-toolbar">
                <p class="mrf-intro text-muted">
                    Diese Filter liefert JTL Search auf der Suchseite. Schalter und Reihenfolge wirken sofort, Titel und Darstellung über
                    <span class="fal fa-edit"></span>.
                    <a href="#mrf-jtl-help" data-toggle="collapse" aria-expanded="false" aria-controls="mrf-jtl-help">
                        <span class="fal fa-info-circle"></span> So funktioniert’s
                    </a>
                </p>
                <div class="mrf-tools">
                    {if $mrfJtlCount > 6}
                        <input type="search" class="form-control mrf-filter-input" id="mrf-jtl-filter" placeholder="Gruppe suchen …" autocomplete="off" aria-label="Gruppe suchen">
                    {/if}
                    {if $mrfJtlShopURL !== ''}
                        <form class="input-group mrf-try" id="mrf-jtl-try" data-url="{$mrfJtlShopURL|escape:'html'}/?qs=">
                            <input type="search" class="form-control" value="burton" aria-label="Suchbegriff für die Testsuche">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-primary" title="Suchseite im Shop in neuem Tab öffnen">
                                    <span class="fal fa-external-link"></span> Suchseite
                                </button>
                            </div>
                        </form>
                    {/if}
                </div>
            </div>
            <div class="collapse mb-3" id="mrf-jtl-help">
                <div class="alert alert-info mrf-help mb-0">
                    <ul class="mb-0 pl-3">
                        <li><strong>Welche</strong> Filter es gibt, legen Sie im JTL-Kundencenter unter JTL-Search → Verwaltung → Filtermöglichkeiten fest. Jede Gruppe, die JTL Search im Shop liefert, erscheint hier automatisch.</li>
                        <li><strong>Regler</strong> liefert JTL Search nur für Zahlenwerte (Merkmalwerte ohne Einheit in der Wawi, z. B. Brettlänge „154“). Einheit und Schrittweite stellen Sie hier ein.</li>
                        <li><strong>Titel</strong> gibt es je Sprache. Ein leeres Feld zeigt den Titel, den JTL Search in dieser Sprache liefert.</li>
                        <li>Im Englischen liefert JTL Search teils <strong>eigene Gruppen</strong> (Kennzeichen „nur diese Sprache“) – doppelte einfach ausblenden.</li>
                        <li>Änderungen erscheinen auf bereits zwischengespeicherten Suchseiten erst nach dem Leeren des <strong>LiteSpeed-Caches</strong>.</li>
                    </ul>
                </div>
            </div>

            {if $mrfJtlCount === 0}
                <div class="mrf-empty">
                    <span class="mrf-empty-icon"><span class="fal fa-search"></span></span>
                    Noch keine Filtergruppen bekannt – einmal im Shop suchen (z. B. rechts über „Suchseite“), danach diese Seite neu laden.
                </div>
            {else}
                {if $mrfJtlSections.visible|count > 0}
                    {call name=mrfJtlTable mrfRows=$mrfJtlSections.visible mrfDrag=true}
                {else}
                    <div class="mrf-empty">Alle Gruppen sind ausgeblendet – auf der Suchseite erscheinen keine Filter.</div>
                {/if}

                {if $mrfJtlSections.hidden|count > 0}
                    <button type="button" class="mrf-section-toggle mt-3" data-toggle="collapse" data-target="#mrf-jtl-hidden" aria-expanded="false" aria-controls="mrf-jtl-hidden">
                        <span class="fal fa-eye-slash"></span> Ausgeblendet <span class="mrf-count">({$mrfJtlSections.hidden|count})</span>
                        <span class="fal fa-chevron-down ml-auto"></span>
                    </button>
                    <div class="collapse mrf-section-body" id="mrf-jtl-hidden">
                        {call name=mrfJtlTable mrfRows=$mrfJtlSections.hidden mrfDrag=false}
                    </div>
                {/if}

                {if $mrfJtlSections.stale|count > 0}
                    <button type="button" class="mrf-section-toggle mt-3" data-toggle="collapse" data-target="#mrf-jtl-stale" aria-expanded="false" aria-controls="mrf-jtl-stale">
                        <span class="fal fa-history"></span> Nicht mehr geliefert <span class="mrf-count">({$mrfJtlSections.stale|count})</span>
                        <span class="fal fa-chevron-down ml-auto"></span>
                    </button>
                    <div class="collapse mrf-section-body" id="mrf-jtl-stale">
                        <p class="text-muted small">Diese Gruppen hat JTL Search seit über {$mrfJtlStaleDays} Tagen nicht mehr geliefert (z. B. nach einer Umstellung in der Wawi). Sie können aus der Liste entfernt werden – kommen sie wieder, erscheinen sie automatisch neu.</p>
                        {call name=mrfJtlTable mrfRows=$mrfJtlSections.stale mrfDrag=false}
                    </div>
                {/if}
            {/if}
        </div>
    </div>

    {* Formulare für Sofort-Aktionen (Schalter, Reihenfolge, Entfernen) *}
    <form method="post" id="mrf-jtl-order-form" class="d-none">
        {$jtl_token}
        <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
        <input type="hidden" name="mrf_action" value="jtl_reorder">
    </form>
    <form method="post" id="mrf-jtl-toggle-form" class="d-none">
        {$jtl_token}
        <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
        <input type="hidden" name="mrf_action" value="jtl_toggle">
        <input type="hidden" name="mrf_jtl_name" value="">
        <input type="hidden" name="mrf_active" value="">
    </form>
    <form method="post" id="mrf-jtl-forget-form" class="d-none">
        {$jtl_token}
        <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
        <input type="hidden" name="mrf_action" value="jtl_forget">
    </form>

    {* ---------- Bearbeiten-Dialoge ---------- *}
    {foreach $mrfJtlSections as $rows}
        {foreach $rows as $row}
            <div class="modal fade mrf-jtl-modal" id="mrf-jtl-edit-{$row.id}" tabindex="-1" role="dialog" aria-labelledby="mrf-jtl-edit-title-{$row.id}">
                <div class="modal-dialog modal-lg" role="document">
                    <form method="post" class="modal-content mrf-jtl-edit-form">
                        {$jtl_token}
                        <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                        <input type="hidden" name="mrf_action" value="jtl_edit">
                        <input type="hidden" name="mrf_jtl_name" value="{$row.name|escape:'html'}">
                        <div class="modal-header">
                            <h2 class="modal-title" id="mrf-jtl-edit-title-{$row.id}">{$row.mainTitle|escape:'html'} bearbeiten</h2>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                                <i class="fal fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="mrf-section-label">Titel im Shop</div>
                            <div class="form-row">
                                {foreach $row.shopTitles as $title}
                                    <div class="form-group col-sm-6">
                                        <div class="input-group">
                                            {if $row.shopTitles|count > 1}
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text mrf-lang" title="{$title.name|escape:'html'}">{$title.label}</span>
                                                </div>
                                            {/if}
                                            <input type="text" class="form-control mrf-jtl-title-input" name="mrf[titles][{$title.iso}]" maxlength="100"
                                                   value="{$title.value|escape:'html'}" aria-label="Titel {$title.name|escape:'html'}"
                                                   placeholder="{if $title@first}{$row.jtlTitle|escape:'html'}{else}wie JTL Search{/if}">
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                            <small class="form-text text-muted mt-n2 mb-3">Leer lassen = Titel von JTL Search{if $row.shopTitles|count > 1} in der jeweiligen Sprache{/if}.</small>

                            {if $row.isSlider}
                                <input type="hidden" name="mrf[display]" value="auto">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="mrf-section-label">Regler</div>
                                        <p class="text-muted small">JTL Search liefert diese Gruppe als Regler (Zahlenwerte). Spanne und Grenzen kommen von JTL Search.</p>
                                        <div class="form-row">
                                            <div class="form-group col-sm-6">
                                                <label for="mrf-jtl-unit-{$row.id}">Einheit</label>
                                                <input type="text" class="form-control mrf-jtl-unit" id="mrf-jtl-unit-{$row.id}" name="mrf[unit]" maxlength="20"
                                                       value="{$row.unit|escape:'html'}" placeholder="z. B. cm">
                                            </div>
                                            <div class="form-group col-sm-6">
                                                <label for="mrf-jtl-step-{$row.id}">Schrittweite</label>
                                                <input type="text" inputmode="decimal" class="form-control" id="mrf-jtl-step-{$row.id}" name="mrf[step]"
                                                       value="{if $row.step > 0}{$row.step|string_format:'%g'|replace:'.':','}{/if}" placeholder="wie JTL Search">
                                                <small class="form-text text-muted">z. B. 1 oder 0,5</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mrf-section-label">Vorschau im Shop</div>
                                        <div class="mrf-preview">
                                            <div class="mrf-preview-frame">
                                                <div class="mrf-pv-title mrf-pv-name">{$row.mainTitle|escape:'html'}</div>
                                                <div class="mrf-pv-inputs">
                                                    <div class="mrf-pv-input"><span>150</span><span class="u">{$row.unit|escape:'html'}</span></div>
                                                    <div class="mrf-pv-input"><span>158</span><span class="u">{$row.unit|escape:'html'}</span></div>
                                                </div>
                                                <div class="mrf-pv-track"></div>
                                                <div class="mrf-pv-note">Beispielwerte</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {else}
                                <div class="mrf-section-label">Darstellung</div>
                                <div class="mrf-tiles" role="radiogroup" aria-label="Darstellung">
                                    {foreach $mrfJtlDisplayLabels as $value => $label}
                                        <label class="mrf-tile{if $row.display === $value} is-selected{/if}" for="mrf-jtl-d-{$row.id}-{$value}">
                                            <input type="radio" id="mrf-jtl-d-{$row.id}-{$value}" name="mrf[display]" value="{$value}"{if $row.display === $value} checked{/if}>
                                            {if $value === 'buttons'}
                                                <span class="mrf-art"><span class="b"><i></i><i></i><i></i></span></span>
                                                <span class="mrf-tile-title">Boxen</span>
                                                <span class="mrf-tile-text">Werte als Buttons, gut für Größen</span>
                                            {elseif $value === 'list'}
                                                <span class="mrf-art"><span class="l"><i></i><i></i><i></i></span></span>
                                                <span class="mrf-tile-title">Checkliste</span>
                                                <span class="mrf-tile-text">Werte mit Häkchen und Anzahl</span>
                                            {else}
                                                <span class="mrf-art"><span class="j fal fa-magic"></span></span>
                                                <span class="mrf-tile-title">wie JTL Search</span>
                                                <span class="mrf-tile-text">aktuell: {$row.typeLabel}</span>
                                            {/if}
                                        </label>
                                    {/foreach}
                                </div>
                            {/if}

                            <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" id="mrf-jtl-expanded-{$row.id}" name="mrf[expanded]" value="1"{if $row.expanded} checked{/if}>
                                <label class="custom-control-label" for="mrf-jtl-expanded-{$row.id}">Im Filter aufgeklappt anzeigen</label>
                            </div>

                            <a class="d-inline-block mt-4 small" href="#mrf-jtl-tech-{$row.id}" data-toggle="collapse" aria-expanded="false" aria-controls="mrf-jtl-tech-{$row.id}">
                                <span class="fal fa-cog"></span> Technische Angaben
                            </a>
                            <div class="collapse" id="mrf-jtl-tech-{$row.id}">
                                <dl class="row mrf-tech mt-2 mb-0">
                                    <dt class="col-sm-4">Feld bei JTL Search</dt><dd class="col-sm-8"><code>{$row.name|escape:'html'}</code></dd>
                                    <dt class="col-sm-4">Titel von JTL Search</dt><dd class="col-sm-8">{$row.jtlTitle|escape:'html'}</dd>
                                    <dt class="col-sm-4">Typ bei JTL Search</dt><dd class="col-sm-8">{$row.typeLabel}</dd>
                                    {if $mrfJtlLanguages|count > 1}
                                        <dt class="col-sm-4">Geliefert in</dt><dd class="col-sm-8">{foreach $row.langLabels as $language}{if !$language@first}, {/if}{$language.name|escape:'html'}{foreachelse}–{/foreach}</dd>
                                    {/if}
                                    <dt class="col-sm-4">Zuletzt geliefert</dt><dd class="col-sm-8">{if $row.daysAgo === null}unbekannt{elseif $row.daysAgo === 0}heute{elseif $row.daysAgo === 1}gestern{else}vor {$row.daysAgo} Tagen{/if}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row">
                                <div class="ml-auto col-sm-6 col-xl-auto submit">
                                    <button type="button" class="btn btn-outline-primary btn-block" data-dismiss="modal">Abbrechen</button>
                                </div>
                                <div class="col-sm-6 col-xl-auto submit">
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fal fa-save"></i> Speichern</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        {/foreach}
    {/foreach}
</div>

<script>
(function () {
    'use strict';
    var root = document.querySelector('.mrf-jtl');
    if (!root) {
        return;
    }

    /* switch → saved immediately */
    var toggleForm = document.getElementById('mrf-jtl-toggle-form');
    root.querySelectorAll('.mrf-jtl-toggle').forEach(function (box) {
        box.addEventListener('change', function () {
            toggleForm.querySelector('[name="mrf_jtl_name"]').value = box.getAttribute('data-name');
            toggleForm.querySelector('[name="mrf_active"]').value = box.checked ? '1' : '0';
            toggleForm.submit();
        });
    });

    /* order by drag & drop → saved right after dropping */
    var body = document.getElementById('mrf-jtl-sortable');
    if (body) {
        var dragged = null;
        var before  = '';
        var orderOf = function () {
            return Array.prototype.map.call(body.querySelectorAll('[name="mrf_jtl_order[]"]'), function (i) { return i.value; }).join('|');
        };
        body.addEventListener('dragstart', function (e) {
            dragged = e.target.closest('tr');
            if (!dragged) {
                return;
            }
            before = orderOf();
            dragged.classList.add('mrf-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', '');
        });
        body.addEventListener('dragover', function (e) {
            var over = e.target.closest('tr');
            if (!dragged || !over || over === dragged) {
                return;
            }
            e.preventDefault();
            var box = over.getBoundingClientRect();
            body.insertBefore(dragged, e.clientY < box.top + box.height / 2 ? over : over.nextSibling);
        });
        body.addEventListener('drop', function (e) {
            // Firefox would otherwise open the dragged text as URL
            e.preventDefault();
        });
        body.addEventListener('dragend', function () {
            if (!dragged) {
                return;
            }
            dragged.classList.remove('mrf-dragging');
            dragged = null;
            if (orderOf() !== before) {
                document.getElementById('mrf-jtl-order-form').submit();
            }
        });
    }

    /* remove (only "no longer delivered") */
    root.querySelectorAll('.mrf-jtl-forget').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!window.confirm('„' + btn.getAttribute('data-title') + '“ aus der Liste entfernen? Liefert JTL Search die Gruppe wieder, erscheint sie erneut mit Standardeinstellungen.')) {
                e.preventDefault();
            }
        });
    });

    /* search in the list (all sections; opens collapsed sections with hits) */
    var filter = document.getElementById('mrf-jtl-filter');
    if (filter) {
        filter.addEventListener('input', function () {
            var q = filter.value.trim().toLowerCase();
            root.querySelectorAll('.table-responsive').forEach(function (wrap) {
                var hits = 0;
                wrap.querySelectorAll('.mrf-jtl-row').forEach(function (row) {
                    var show = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                    row.style.display = show ? '' : 'none';
                    hits += show ? 1 : 0;
                });
                wrap.querySelector('.mrf-nohit').style.display = hits === 0 ? 'block' : 'none';
                var section = wrap.closest('.mrf-section-body');
                if (section && q !== '' && hits > 0 && window.jQuery) {
                    window.jQuery(section).collapse('show');
                }
            });
        });
    }

    /* test search in the shop */
    var tryForm = document.getElementById('mrf-jtl-try');
    if (tryForm) {
        tryForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var term = tryForm.querySelector('input').value.trim();
            if (term !== '') {
                window.open(tryForm.getAttribute('data-url') + encodeURIComponent(term), '_blank', 'noopener');
            }
        });
    }

    /* dialogs: tiles, live preview of title and unit */
    document.querySelectorAll('.mrf-jtl-edit-form').forEach(function (form) {
        form.querySelectorAll('.mrf-tile input').forEach(function (radio) {
            radio.addEventListener('change', function () {
                form.querySelectorAll('.mrf-tile').forEach(function (tile) {
                    tile.classList.toggle('is-selected', tile.querySelector('input').checked);
                });
            });
        });
        var unit  = form.querySelector('.mrf-jtl-unit');
        var title = form.querySelector('.mrf-jtl-title-input');
        var name  = form.querySelector('.mrf-pv-name');
        if (unit) {
            unit.addEventListener('input', function () {
                form.querySelectorAll('.mrf-pv-input .u').forEach(function (u) { u.textContent = unit.value.trim(); });
            });
        }
        if (title && name) {
            title.addEventListener('input', function () {
                name.textContent = title.value.trim() || title.getAttribute('placeholder');
            });
        }
    });
})();
</script>
