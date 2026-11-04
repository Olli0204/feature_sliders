{* Tab „JTL Search“: Einstellungen je Filtergruppe des Plugins JTL Search (Suchseite). *}
<style>
    .mrf-jtl .mrf-drag-cell { width: 2rem; cursor: grab; opacity: .6; }
    .mrf-jtl tr.mrf-dragging { opacity: .4; }
    .mrf-jtl tr.mrf-off td:not(.mrf-keep) { opacity: .45; }
    .mrf-jtl .mrf-field { font-size: .75rem; opacity: .7; word-break: break-all; }
    .mrf-jtl .mrf-chip { display: inline-block; padding: .05rem .45rem; font-size: .75rem; background: var(--table-accent-color, #f5f7fa); border: 1px solid var(--border-color, #e4e9f2); border-radius: .75rem; white-space: nowrap; }
    .mrf-jtl .form-control { min-width: 5rem; }
    .mrf-jtl .mrf-col-title .form-control { min-width: 11rem; }
    .mrf-jtl .mrf-empty { padding: 2.5rem 1rem; text-align: center; }
    .mrf-jtl .mrf-empty-icon { display: block; margin: 0 auto .75rem; font-size: 2rem; line-height: 1; opacity: .35; }
    .mrf-jtl td { vertical-align: middle !important; }
</style>

<div class="mrf-jtl">
    {if $mrfMessage !== null}
        <div class="alert alert-{$mrfMessage.type}">{$mrfMessage.text|escape:'html'}</div>
    {/if}
    {if !$mrfJtlEnabled}
        <div class="alert alert-warning">
            Die Einstellung „JTL-Search-Filter im Shop-Design“ (oder das ganze Plugin) ist ausgeschaltet – auf der Suchseite
            zeigt die Box „Filter (JTL Search)“ die Filter, diese Einstellungen wirken dann nicht.
        </div>
    {/if}

    <div class="card">
        <div class="card-header">
            <div class="subheading1">Filter der Suchseite (JTL Search)</div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Welche Filter JTL Search liefert, wird im JTL-Kundencenter unter JTL-Search → Verwaltung → Filtermöglichkeiten festgelegt.
                Jede Gruppe, die JTL Search im Shop liefert, erscheint hier automatisch. Leere Felder übernehmen die Angaben von JTL Search.
                Die Reihenfolge lässt sich per Ziehen ändern. Regler kommen nur für Zahlenwerte von JTL Search, Schrittweite und Einheit
                gelten nur für Regler.
            </p>
            {if $mrfJtlRows|count === 0}
                <div class="mrf-empty">
                    <span class="mrf-empty-icon"><span class="fal fa-search"></span></span>
                    Noch keine Filtergruppen bekannt – einmal im Shop suchen (z. B. nach einem Markennamen), danach diese Seite neu laden.
                </div>
            {else}
                <form method="post" id="mrf-jtl-form">
                    {$jtl_token}
                    <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                    <input type="hidden" name="mrf_action" value="jtl_save">
                    <div class="table-responsive">
                        <table class="list table mb-3">
                            <thead>
                                <tr>
                                    <th class="mrf-drag-cell"><span class="sr-only">Reihenfolge</span></th>
                                    <th class="text-center">Anzeigen</th>
                                    <th>Gruppe von JTL Search</th>
                                    <th class="mrf-col-title">Titel im Shop</th>
                                    <th>Darstellung</th>
                                    <th>Einheit</th>
                                    <th>Schrittweite</th>
                                    <th class="text-center">Aufgeklappt</th>
                                    <th class="text-center">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody id="mrf-jtl-rows">
                                {foreach $mrfJtlRows as $row}
                                    {assign var=field value="mrf_jtl[{$row.name}]"}
                                    <tr draggable="true" class="{if !$row.active}mrf-off{/if}">
                                        <td class="mrf-drag-cell mrf-keep" title="Reihenfolge per Ziehen ändern">
                                            <span class="fal fa-grip-vertical"></span>
                                            <input type="hidden" name="{$field}[present]" value="1">
                                        </td>
                                        <td class="text-center mrf-keep">
                                            <input type="checkbox" class="mrf-jtl-active" name="{$field}[active]" value="1"{if $row.active} checked{/if}
                                                   aria-label="{$row.jtlTitle|escape:'html'} anzeigen">
                                        </td>
                                        <td>
                                            <strong>{$row.jtlTitle|escape:'html'}</strong> <span class="mrf-chip">{$row.typeLabel}</span><br>
                                            <span class="mrf-field">{$row.name|escape:'html'}</span>
                                            {if $row.daysAgo === null || $row.daysAgo > 14}
                                                <br><span class="text-warning small">seit {if $row.daysAgo === null}längerem{else}{$row.daysAgo} Tagen{/if} nicht mehr von JTL Search geliefert</span>
                                            {/if}
                                        </td>
                                        <td class="mrf-col-title">
                                            <input type="text" class="form-control" name="{$field}[title]" maxlength="100"
                                                   value="{$row.title|escape:'html'}" placeholder="{$row.jtlTitle|escape:'html'}">
                                        </td>
                                        <td>
                                            {if $row.isSlider}
                                                Regler
                                                <input type="hidden" name="{$field}[display]" value="auto">
                                            {else}
                                                <select class="custom-select" name="{$field}[display]">
                                                    {foreach $mrfJtlDisplayLabels as $value => $label}
                                                        <option value="{$value}"{if $row.display === $value} selected{/if}>{$label}{if $value === 'auto'} ({$row.typeLabel}){/if}</option>
                                                    {/foreach}
                                                </select>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $row.isSlider}
                                                <input type="text" class="form-control" name="{$field}[unit]" maxlength="20"
                                                       value="{$row.unit|escape:'html'}" placeholder="z. B. cm">
                                            {else}
                                                <span class="text-muted">–</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $row.isSlider}
                                                <input type="number" class="form-control" name="{$field}[step]" min="0" step="any"
                                                       value="{if $row.step > 0}{$row.step}{/if}" placeholder="wie JTL">
                                            {else}
                                                <span class="text-muted">–</span>
                                            {/if}
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="{$field}[expanded]" value="1"{if $row.expanded} checked{/if}
                                                   aria-label="{$row.jtlTitle|escape:'html'} aufgeklappt">
                                        </td>
                                        <td class="text-center mrf-keep">
                                            <button type="submit" class="btn btn-link px-2 mrf-jtl-forget" form="mrf-jtl-forget-form"
                                                    name="mrf_jtl_name" value="{$row.name|escape:'html'}"
                                                    data-name="{$row.jtlTitle|escape:'html'}" title="Aus der Liste entfernen">
                                                <span class="icon-hover">
                                                    <span class="fal fa-trash-alt"></span>
                                                    <span class="fas fa-trash-alt"></span>
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                    <div class="save-wrapper">
                        <div class="row">
                            <div class="ml-auto col-sm-6 col-xl-auto">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fal fa-save"></i> Speichern
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <form method="post" id="mrf-jtl-forget-form" class="d-none">
                    {$jtl_token}
                    <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                    <input type="hidden" name="mrf_action" value="jtl_forget">
                </form>
            {/if}
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var body = document.getElementById('mrf-jtl-rows');
    if (!body) {
        return;
    }
    /* order by drag & drop – saved together with the other fields ("Speichern") */
    var dragged = null;
    body.addEventListener('dragstart', function (e) {
        if (e.target.closest('input, select, button')) {
            return;
        }
        dragged = e.target.closest('tr');
        if (dragged) {
            dragged.classList.add('mrf-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', '');
        }
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
        if (dragged) {
            dragged.classList.remove('mrf-dragging');
            dragged = null;
        }
    });
    /* inputs inside draggable rows: allow text selection */
    body.querySelectorAll('input, select').forEach(function (el) {
        el.addEventListener('mousedown', function () { el.closest('tr').setAttribute('draggable', 'false'); });
        el.addEventListener('blur', function () { el.closest('tr').setAttribute('draggable', 'true'); });
    });
    body.querySelectorAll('.mrf-jtl-active').forEach(function (box) {
        box.addEventListener('change', function () { box.closest('tr').classList.toggle('mrf-off', !box.checked); });
    });
    body.querySelectorAll('.mrf-jtl-forget').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!window.confirm('„' + btn.getAttribute('data-name') + '“ aus der Liste entfernen? Liefert JTL Search die Gruppe wieder, erscheint sie erneut mit Standardeinstellungen.')) {
                e.preventDefault();
            }
        });
    });
})();
</script>
