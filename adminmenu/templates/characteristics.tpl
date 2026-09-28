{* Tab „Merkmale“: Übersicht der eingestellten Merkmale, Hinzufügen- und Bearbeiten-Dialoge mit Live-Vorschau.
   Bausteine wie im JTL-Backend (card/subheading1, table.list, custom-switch, btn-link + icon-hover, modal). *}
<style>
    .mrf-admin .mrf-samples { font-size: .8125rem; }
    .mrf-admin .mrf-row-inactive td:not(.mrf-col-active):not(.mrf-col-actions) { opacity: .45; }
    .mrf-admin .mrf-display-label { white-space: nowrap; }
    .mrf-admin .mrf-display-label .fal { width: 1.25rem; }
    .mrf-admin .mrf-empty { padding: 2.5rem 1rem; text-align: center; }
    .mrf-admin .mrf-empty .fal { font-size: 2rem; opacity: .35; display: block; margin-bottom: .75rem; }
    .mrf-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr)); gap: .75rem; }
    .mrf-tile { position: relative; display: block; margin: 0; padding: .75rem; border: 1px solid #d6dbe1; border-radius: .25rem; cursor: pointer; background: #fff; }
    .mrf-tile:hover { border-color: var(--primary, #1d6fb8); }
    .mrf-tile input { position: absolute; opacity: 0; pointer-events: none; }
    .mrf-tile.is-selected { border-color: var(--primary, #1d6fb8); box-shadow: 0 0 0 1px var(--primary, #1d6fb8); }
    .mrf-tile-title { display: block; font-weight: 600; font-size: .875rem; margin-top: .5rem; }
    .mrf-tile-text { display: block; font-size: .75rem; color: #6c757d; line-height: 1.3; margin-top: .125rem; }
    .mrf-tile-art { display: block; height: 2rem; }
    .mrf-tile-art .t { position: relative; display: block; height: 3px; margin: .9rem .35rem 0; background: #ffa54f; border-radius: 2px; }
    .mrf-tile-art .t::before, .mrf-tile-art .t::after { content: ''; position: absolute; top: -4px; width: 11px; height: 11px; border-radius: 50%; background: #ffa54f; box-shadow: 0 0 0 3px rgba(255, 165, 79, .35); }
    .mrf-tile-art .t::before { left: -4px; }
    .mrf-tile-art .t::after { right: -4px; }
    .mrf-tile-art .t.single::before { left: 30%; }
    .mrf-tile-art .t.single { background: linear-gradient(90deg, #e3e3e3 30%, #ffa54f 30%); }
    .mrf-tile-art .b { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; padding-top: .35rem; }
    .mrf-tile-art .b i { display: block; height: 1.1rem; border: 1px solid #707070; border-radius: 2px; }
    .mrf-tile-art .b i:nth-child(2) { background: #ffa54f; border-color: #ffa54f; }
    .mrf-preview { background: #f5f7fa; border-radius: .25rem; padding: 1rem; }
    .mrf-preview-frame { background: #fff; border: 1px solid #e3e6ea; border-radius: .25rem; padding: .75rem 1rem 1rem; max-width: 300px; margin: 0 auto; color: #525252; font-family: 'Open Sans', sans-serif; }
    .mrf-preview-title { display: flex; justify-content: space-between; align-items: center; font-size: 1rem; margin-bottom: .75rem; }
    .mrf-pv-inputs { display: flex; justify-content: space-between; margin-bottom: 1.1rem; }
    .mrf-pv-input { display: flex; width: 41.6%; height: 1.6rem; border: 1px solid #707070; border-radius: .125rem; font-weight: 700; font-size: .875rem; overflow: hidden; }
    .mrf-pv-input span:first-child { flex: 1; padding: 0 .5rem; line-height: 1.5rem; }
    .mrf-pv-input .u { padding: 0 .5rem; line-height: 1.5rem; background: #ebebeb; border-left: 1px solid #707070; }
    .mrf-pv-track { position: relative; height: .3em; margin: 0 .5rem .5rem; background: #ffa54f; }
    .mrf-pv-track::before, .mrf-pv-track::after { content: ''; position: absolute; top: -.2em; width: .7em; height: .7em; border-radius: 50%; background: #ffa54f; box-shadow: 0 0 0 5px rgba(255, 165, 79, .5); }
    .mrf-pv-track::before { left: -.35em; }
    .mrf-pv-track::after { right: -.35em; }
    .mrf-pv-buttons { display: grid; grid-template-columns: repeat(var(--cols, 3), minmax(0, 1fr)); gap: .75rem 1.25rem; padding: .25rem .5rem; }
    .mrf-pv-buttons.c2, .mrf-pv-buttons.c4 { column-gap: .75rem; }
    .mrf-pv-buttons span { display: flex; align-items: center; justify-content: center; min-height: 1.875rem; padding: .25rem .375rem; font-size: .875rem; font-weight: 600; line-height: 1.2; text-align: center; overflow-wrap: anywhere; border: 1px solid #707070; border-radius: .1875rem; }
    .mrf-pv-buttons span:nth-child(2) { background: #ffa54f; border-color: #ffa54f; color: #fff; }
    .mrf-pv-note { font-size: .75rem; color: #6c757d; text-align: center; margin-top: .75rem; }
    .mrf-add-list { max-height: 18rem; overflow-y: auto; border: 1px solid #d6dbe1; border-radius: .25rem; }
    .mrf-add-item { display: flex; gap: .75rem; align-items: flex-start; margin: 0; padding: .5rem .75rem; border-bottom: 1px solid #eef0f3; cursor: pointer; font-weight: 400; }
    .mrf-add-item:last-child { border-bottom: 0; }
    .mrf-add-item:hover { background: #f5f7fa; }
    .mrf-add-item input { margin-top: .3rem; }
    .mrf-section-label { font-weight: 600; margin-bottom: .5rem; }
    /* JTL-Theme zeigt „an“ nur als weißen Punkt auf transparentem Grund – hier klar in der Backend-Akzentfarbe */
    .mrf-admin .custom-switch .custom-control-input:checked ~ .custom-control-label::before { background-color: #5cbcf6; border-color: #5cbcf6; }
    .mrf-admin .mrf-state { display: block; font-size: .75rem; color: #6c757d; margin-top: .125rem; }
</style>

<div class="mrf-admin">
    {if $mrfMessage !== null}
        <div class="alert alert-{$mrfMessage.type}">{$mrfMessage.text|escape:'html'}</div>
    {/if}
    {if !$mrfActive}
        <div class="alert alert-warning">
            Das Plugin ist unter <strong>Einstellungen</strong> ausgeschaltet – im Shop erscheinen gerade überall die normalen Merkmalfilter.
        </div>
    {/if}

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <div class="subheading1 mr-auto">Filter-Darstellung</div>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#mrf-add-modal"{if $mrfAvailable|count === 0} disabled{/if}>
                <i class="fal fa-plus"></i> Merkmal hinzufügen
            </button>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Hier legen Sie fest, welche Merkmale im Filter der Artikelliste als Schieberegler oder als Boxen erscheinen.
                Alle anderen Merkmale zeigen den normalen Checkbox-Filter. Ein deaktivierter Eintrag behält seine Einstellungen,
                im Shop erscheint dann wieder der normale Filter.
            </p>
            {if $mrfRows|count === 0}
                <div class="mrf-empty text-muted">
                    <span class="fal fa-sliders-h"></span>
                    Noch keine Merkmale eingestellt.<br>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" data-toggle="modal" data-target="#mrf-add-modal">
                        <i class="fal fa-plus"></i> Erstes Merkmal hinzufügen
                    </button>
                </div>
            {else}
                <div class="table-responsive">
                    <table class="list table table-align-top mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 5rem;">Aktiv</th>
                                <th>Merkmal</th>
                                <th>Darstellung</th>
                                <th>Einstellungen</th>
                                <th>Werte</th>
                                <th class="text-center" style="width: 7rem;">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $mrfRows as $row}
                                {assign var=cfg value=$row.config}
                                <tr class="{if !$cfg->active}mrf-row-inactive{/if}">
                                    <td class="text-center mrf-col-active">
                                        <form method="post" class="mrf-toggle-form">
                                            {$jtl_token}
                                            <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                                            <input type="hidden" name="mrf_action" value="toggle">
                                            <input type="hidden" name="mrf_id" value="{$row.id}">
                                            <input type="hidden" name="mrf_active" value="{if $cfg->active}0{else}1{/if}">
                                            <div class="custom-control custom-switch d-inline-block" title="{if $cfg->active}Deaktivieren{else}Aktivieren{/if}">
                                                <input type="checkbox" class="custom-control-input mrf-toggle" id="mrf-active-{$row.id}"{if $cfg->active} checked{/if}>
                                                <label class="custom-control-label" for="mrf-active-{$row.id}"><span class="sr-only">Aktiv</span></label>
                                            </div>
                                            <span class="mrf-state">{if $cfg->active}an{else}aus{/if}</span>
                                        </form>
                                    </td>
                                    <td>
                                        <strong>{$row.name|escape:'html'}</strong>
                                        {if $row.samples|count > 0}
                                            <div class="text-muted mrf-samples">{foreach $row.samples as $sample}{if !$sample@first} · {/if}{$sample|escape:'html'}{/foreach}{if $row.valueCount > $row.samples|count} …{/if}</div>
                                        {/if}
                                    </td>
                                    <td class="mrf-display-label">
                                        {if $cfg->isButtons()}<span class="fal fa-th-large"></span>{else}<span class="fal fa-sliders-h"></span>{/if}
                                        {$mrfDisplayLabels[$cfg->display]}
                                    </td>
                                    <td class="text-muted">{$row.summary|escape:'html'}</td>
                                    <td class="text-nowrap">
                                        {if $cfg->isButtons()}
                                            <span class="text-muted">{$row.valueCount} Werte</span>
                                        {elseif $row.invalid > 0}
                                            <span class="text-warning"><span class="fal fa-exclamation-triangle"></span> {$row.invalid} ohne Zahl</span>
                                        {else}
                                            <span class="text-success"><span class="fal fa-check"></span> alle erkannt</span>
                                        {/if}
                                    </td>
                                    <td class="text-center mrf-col-actions">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-link px-2" data-toggle="modal" data-target="#mrf-edit-{$row.id}" title="Bearbeiten">
                                                <span class="icon-hover">
                                                    <span class="fal fa-edit"></span>
                                                    <span class="fas fa-edit"></span>
                                                </span>
                                            </button>
                                            <form method="post" class="d-inline mrf-remove-form" data-name="{$row.name|escape:'html'}">
                                                {$jtl_token}
                                                <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                                                <input type="hidden" name="mrf_action" value="remove">
                                                <input type="hidden" name="mrf_id" value="{$row.id}">
                                                <button type="submit" class="btn btn-link px-2" title="Entfernen">
                                                    <span class="icon-hover">
                                                        <span class="fal fa-trash-alt"></span>
                                                        <span class="fas fa-trash-alt"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>

    {* ---------- Bearbeiten ---------- *}
    {foreach $mrfRows as $row}
        {assign var=cfg value=$row.config}
        <div class="modal fade mrf-edit-modal" id="mrf-edit-{$row.id}" tabindex="-1" role="dialog" aria-labelledby="mrf-edit-title-{$row.id}">
            <div class="modal-dialog modal-xl" role="document">
                <form method="post" class="modal-content mrf-config-form" data-preview="{$row.preview|escape:'html'}">
                    {$jtl_token}
                    <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                    <input type="hidden" name="mrf_action" value="save">
                    <input type="hidden" name="mrf_id" value="{$row.id}">
                    <div class="modal-header">
                        <h2 class="modal-title" id="mrf-edit-title-{$row.id}">{$row.name|escape:'html'} bearbeiten</h2>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                            <i class="fal fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="mrf-section-label">Darstellung</div>
                                {include file=$mrfTilesTpl mrfSelected=$cfg->display mrfTileId="edit-`$row.id`"}

                                <div class="mt-4" data-show="slider">
                                    <div class="form-row">
                                        <div class="form-group col-sm-6">
                                            <label for="mrf-unit-{$row.id}">Einheit</label>
                                            <input type="text" class="form-control" id="mrf-unit-{$row.id}" name="mrf[unit]" maxlength="20"
                                                   value="{$cfg->unit|escape:'html'}"
                                                   placeholder="automatisch{if $row.analysis.range.unit !== ''}: {$row.analysis.range.unit|escape:'html'}{/if}">
                                            <small class="form-text text-muted">Leer lassen = aus den Wawi-Werten übernehmen.</small>
                                        </div>
                                        <div class="form-group col-sm-6">
                                            <label for="mrf-step-{$row.id}">Schrittweite</label>
                                            <input type="text" inputmode="decimal" class="form-control" id="mrf-step-{$row.id}" name="mrf[step]"
                                                   value="{$cfg->step|string_format:'%g'}">
                                            <small class="form-text text-muted">z. B. 1, 5 oder 0,5</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group" data-show="slider_range">
                                    <label for="mrf-mode-{$row.id}">Treffer-Logik</label>
                                    <select class="custom-select" id="mrf-mode-{$row.id}" name="mrf[mode]">
                                        {foreach $mrfModeLabels as $value => $label}
                                            <option value="{$value}"{if $cfg->mode === $value} selected{/if}>{$label}</option>
                                        {/foreach}
                                    </select>
                                    <small class="form-text text-muted">Wann ein Artikel mit z. B. „35 - 55 kg“ zur eingestellten Auswahl passt. Standard: sobald sich die Bereiche überschneiden.</small>
                                </div>
                                <div class="form-group mt-4" data-show="buttons">
                                    <label for="mrf-cols-{$row.id}">Boxen pro Zeile</label>
                                    <select class="custom-select" id="mrf-cols-{$row.id}" name="mrf[button_columns]">
                                        <option value="0"{if $cfg->buttonColumns === 0} selected{/if}>Automatisch nach Wertlänge (aktuell {$row.autoColumns})</option>
                                        {foreach [1, 2, 3, 4] as $cols}
                                            <option value="{$cols}"{if $cfg->buttonColumns === $cols} selected{/if}>{$cols}</option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input" id="mrf-expanded-{$row.id}" name="mrf[expanded]" value="1"{if $cfg->expanded} checked{/if}>
                                    <label class="custom-control-label" for="mrf-expanded-{$row.id}">Im Filter aufgeklappt anzeigen</label>
                                </div>
                            </div>
                            <div class="col-lg-5 mt-4 mt-lg-0">
                                <div class="mrf-section-label">Vorschau im Shop</div>
                                <div class="mrf-preview"><div class="mrf-preview-frame"></div></div>

                                <div class="mrf-section-label mt-4 mrf-check-heading">Werteprüfung</div>
                                {foreach ['range', 'single'] as $kind}
                                    {assign var=check value=$row.analysis[$kind]}
                                    <div class="mrf-check" data-kind="{$kind}">
                                        {if $check.invalid > 0}
                                            <div class="alert alert-warning py-2 small mb-2">
                                                {$check.invalid} Wert(e) ohne Zahl – Artikel mit diesen Werten erscheinen nicht, sobald der Regler benutzt wird.
                                            </div>
                                        {else}
                                            <div class="text-success small mb-2"><span class="fal fa-check"></span> Alle {$check.rows|count} Werte erkannt.</div>
                                        {/if}
                                        <div class="table-responsive" style="max-height: 16rem;">
                                            <table class="table table-sm table-striped small mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Wert</th>
                                                        <th class="text-right">{if $kind === 'single'}Zahl{else}von{/if}</th>
                                                        {if $kind === 'range'}<th class="text-right">bis</th>{/if}
                                                        <th class="text-right">Artikel</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {foreach $check.rows as $value}
                                                        <tr{if !$value.ok} class="text-warning"{/if}>
                                                            <td>{if !$value.ok}<span class="fal fa-exclamation-triangle"></span> {/if}{$value.text|escape:'html'}</td>
                                                            <td class="text-right">{$value.min|escape:'html'}</td>
                                                            {if $kind === 'range'}<td class="text-right">{$value.max|escape:'html'}</td>{/if}
                                                            <td class="text-right">{$value.products}</td>
                                                        </tr>
                                                    {foreachelse}
                                                        <tr><td colspan="4">Keine Werte vorhanden.</td></tr>
                                                    {/foreach}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
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

    {* ---------- Hinzufügen ---------- *}
    <div class="modal fade" id="mrf-add-modal" tabindex="-1" role="dialog" aria-labelledby="mrf-add-title">
        <div class="modal-dialog modal-lg" role="document">
            <form method="post" class="modal-content mrf-add-form">
                {$jtl_token}
                <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                <input type="hidden" name="mrf_action" value="save">
                <input type="hidden" name="mrf[expanded]" value="1">
                <div class="modal-header">
                    <h2 class="modal-title" id="mrf-add-title">Merkmal hinzufügen</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                        <i class="fal fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mrf-section-label">1. Merkmal wählen</div>
                    <input type="search" class="form-control mb-2 mrf-add-search" placeholder="Merkmal oder Wert suchen …" autocomplete="off">
                    <div class="mrf-add-list">
                        {foreach $mrfAvailable as $characteristic}
                            <label class="mrf-add-item" data-search="{$characteristic.name|lower|escape:'html'} {foreach $characteristic.samples as $sample}{$sample|lower|escape:'html'} {/foreach}">
                                <input type="radio" name="mrf_id" value="{$characteristic.id}" required>
                                <span>
                                    <strong>{$characteristic.name|escape:'html'}</strong>
                                    <span class="text-muted small">· {$characteristic.valueCount} Werte</span>
                                    {if $characteristic.samples|count > 0}
                                        <span class="d-block text-muted mrf-samples">{foreach $characteristic.samples as $sample}{if !$sample@first} · {/if}{$sample|escape:'html'}{/foreach}{if $characteristic.valueCount > $characteristic.samples|count} …{/if}</span>
                                    {/if}
                                </span>
                            </label>
                        {/foreach}
                        <div class="p-3 text-muted mrf-add-none d-none">Kein Merkmal gefunden.</div>
                    </div>

                    <div class="mrf-section-label mt-4">2. Darstellung wählen</div>
                    {include file=$mrfTilesTpl mrfSelected="" mrfTileId="add"}
                    <p class="text-muted small mt-3 mb-0">Einheit, Schrittweite und weitere Details stellen Sie danach mit Vorschau ein.</p>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="ml-auto col-sm-6 col-xl-auto submit">
                            <button type="button" class="btn btn-outline-primary btn-block" data-dismiss="modal">Abbrechen</button>
                        </div>
                        <div class="col-sm-6 col-xl-auto submit">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fal fa-plus"></i> Hinzufügen</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var root = document.querySelector('.mrf-admin');
    if (!root) {
        return;
    }

    function autoColumns(values) {
        var longest = values.reduce(function (max, v) { return Math.max(max, v.trim().length); }, 0);
        return longest <= 3 ? 4 : longest <= 9 ? 3 : longest <= 16 ? 2 : 1;
    }
    function decimals(step) {
        var parts = String(step).split('.');
        return parts.length > 1 ? parts[1].length : 0;
    }
    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    function selectedDisplay(form) {
        var checked = form.querySelector('input[name="mrf[display]"]:checked');
        return checked ? checked.value : '';
    }
    function syncTiles(form) {
        form.querySelectorAll('.mrf-tile').forEach(function (tile) {
            tile.classList.toggle('is-selected', tile.querySelector('input').checked);
        });
    }
    function syncFields(form) {
        var display = selectedDisplay(form);
        var slider  = display === 'slider_range' || display === 'slider_single';
        form.querySelectorAll('[data-show]').forEach(function (el) {
            var wanted = el.getAttribute('data-show');
            var show   = wanted === display || (wanted === 'slider' && slider);
            el.classList.toggle('d-none', !show);
            el.querySelectorAll('input, select').forEach(function (field) { field.disabled = !show; });
        });
        form.querySelectorAll('.mrf-check').forEach(function (check) {
            var kind = display === 'slider_single' ? 'single' : 'range';
            check.classList.toggle('d-none', !slider || check.getAttribute('data-kind') !== kind);
        });
        var heading = form.querySelector('.mrf-check-heading');
        if (heading) {
            heading.classList.toggle('d-none', !slider);
        }
    }
    function fieldValue(form, name, fallback) {
        var field = form.querySelector('[name="' + name + '"]');
        return field ? field.value : fallback;
    }
    function renderPreview(form) {
        var frame = form.querySelector('.mrf-preview-frame');
        if (!frame) {
            return;
        }
        var data    = JSON.parse(form.getAttribute('data-preview'));
        var display = selectedDisplay(form);
        var html    = '<div class="mrf-preview-title">' + esc(data.name) + ' <span class="fal fa-chevron-up"></span></div>';
        if (display === 'buttons') {
            var fixed = parseInt(fieldValue(form, 'mrf[button_columns]', '0'), 10);
            var cols  = fixed > 0 ? fixed : autoColumns(data.values);
            html += '<div class="mrf-pv-buttons c' + cols + '" style="--cols:' + cols + '">'
                + data.values.map(function (v) { return '<span>' + esc(v) + '</span>'; }).join('') + '</div>'
                + '<div class="mrf-pv-note">Alle ' + data.values.length + ' Werte · im Shop nur die Werte der jeweiligen Kategorie</div>';
        } else {
            var kind = display === 'slider_single' ? data.single : data.range;
            var unit = fieldValue(form, 'mrf[unit]', '').trim() || kind.unit;
            var raw  = fieldValue(form, 'mrf[step]', '1').replace(',', '.');
            var step = parseFloat(raw) > 0 ? parseFloat(raw) : 1;
            if (!kind.extent) {
                html += '<div class="text-warning small">Keine auswertbaren Werte – der Regler wird nicht angezeigt.</div>';
            } else {
                var digits = decimals(raw);
                var min    = (Math.floor(kind.extent.min / step) * step).toFixed(digits);
                var max    = (Math.ceil(kind.extent.max / step) * step).toFixed(digits);
                var input  = function (v) {
                    return '<div class="mrf-pv-input"><span>' + esc(v) + '</span>'
                        + (unit ? '<span class="u">' + esc(unit) + '</span>' : '') + '</div>';
                };
                html += '<div class="mrf-pv-inputs">' + input(min) + input(max) + '</div><div class="mrf-pv-track"></div>'
                    + '<div class="mrf-pv-note">Skala über alle Werte · im Shop je Kategorie</div>';
            }
        }
        frame.innerHTML = html;
    }
    function wire(form) {
        var update = function () {
            syncTiles(form);
            syncFields(form);
            renderPreview(form);
        };
        form.addEventListener('change', update);
        form.addEventListener('input', function (e) {
            if (e.target.name === 'mrf[unit]' || e.target.name === 'mrf[step]') {
                renderPreview(form);
            }
        });
        update();
    }

    root.querySelectorAll('.mrf-config-form').forEach(wire);
    root.querySelectorAll('.mrf-add-form').forEach(function (form) {
        var search = form.querySelector('.mrf-add-search');
        form.addEventListener('change', function () { syncTiles(form); });
        syncTiles(form);
        search.addEventListener('input', function () {
            var term  = search.value.trim().toLowerCase();
            var shown = 0;
            form.querySelectorAll('.mrf-add-item').forEach(function (item) {
                var hit = term === '' || item.getAttribute('data-search').indexOf(term) !== -1;
                item.classList.toggle('d-none', !hit);
                shown += hit ? 1 : 0;
            });
            form.querySelector('.mrf-add-none').classList.toggle('d-none', shown > 0);
        });
    });
    root.querySelectorAll('.mrf-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () { toggle.closest('form').submit(); });
    });
    root.querySelectorAll('.mrf-remove-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var question = '„' + form.getAttribute('data-name') + '“ entfernen? Im Shop erscheint dann wieder der normale Merkmalfilter.';
            if (!window.confirm(question)) {
                e.preventDefault();
            }
        });
    });
    {if $mrfOpenEdit > 0}
    if (window.jQuery) {
        window.jQuery(function () {
            window.jQuery('#mrf-edit-{$mrfOpenEdit}').modal('show');
        });
    }
    {/if}
})();
</script>
