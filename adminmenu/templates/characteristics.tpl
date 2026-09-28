<div class="card mrf-admin">
    <div class="card-header">
        <div class="subheading1">Darstellung der Merkmale im Filter</div>
    </div>
    <div class="card-body">
        {if $mrfSaved === true}
            <div class="alert alert-success">Einstellungen gespeichert.</div>
        {elseif $mrfSaved === false}
            <div class="alert alert-danger">Speichern fehlgeschlagen: ungültiges Sicherheits-Token. Bitte Seite neu laden und erneut speichern.</div>
        {/if}
        {if !$mrfActive}
            <div class="alert alert-warning">Das Plugin ist unter <strong>Einstellungen</strong> deaktiviert – im Shop werden aktuell keine Schieberegler angezeigt.</div>
        {/if}
        <p class="text-muted">
            Legen Sie je Merkmal fest, wie es im Merkmalfilter der Artikelliste erscheint. Schieberegler stehen automatisch
            an der Stelle des Merkmals (Seitenleiste und mobiler Filter-Dialog), Reihenfolge wie in der Wawi.
        </p>
        <ul class="text-muted small mb-3">
            <li><strong>Bereich auf Bereich</strong> – Werte sind Bereiche, z. B. Körpergewicht „35 - 55 kg“. Treffer je nach Treffer-Logik (Standard: Bereiche überschneiden sich).</li>
            <li><strong>Bereich auf Einzelwerte</strong> – Werte sind einzelne Zahlen, z. B. Länge „154 cm“. Treffer, wenn der Wert im eingestellten Bereich liegt.</li>
            <li><strong>Boxen</strong> – die Werte erscheinen als Buttons statt als Checkliste; Auswahl, Mehrfachauswahl und Trefferanzahl wie beim Standard-Filter.</li>
            <li><strong>Einheit</strong> leer lassen = wird aus den Wawi-Werten übernommen (nur Schieberegler).</li>
        </ul>

        <div class="form-group" style="max-width: 320px;">
            <input type="search" class="form-control" id="mrf-search" placeholder="Merkmal suchen …" autocomplete="off">
        </div>

        <form method="post" id="mrf-form">
            {$jtl_token}
            <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
            <input type="hidden" name="mrf_save" value="1">
            <div class="table-responsive">
                <table class="table table-sm table-align-top" id="mrf-table">
                    <thead>
                        <tr>
                            <th>Merkmal</th>
                            <th class="text-right">Werte</th>
                            <th style="min-width: 260px;">Darstellung</th>
                            <th style="min-width: 230px;">Treffer-Logik</th>
                            <th style="width: 110px;">Einheit</th>
                            <th style="width: 100px;">Schrittweite</th>
                            <th class="text-center">Aufgeklappt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $mrfRows as $row}
                            {assign var=cfg value=$row.config}
                            <tr class="mrf-row" data-mrf-id="{$row.id}" data-mrf-name="{$row.name|lower|escape:'html'}">
                                <td>
                                    <strong>{$row.name|escape:'html'}</strong> <span class="text-muted small">(ID {$row.id})</span>
                                    {if $row.samples|count > 0}
                                        <div class="text-muted small">{foreach $row.samples as $sample}{if !$sample@first} · {/if}{$sample|escape:'html'}{/foreach}{if $row.valueCount > $row.samples|count} …{/if}</div>
                                    {/if}
                                </td>
                                <td class="text-right">{$row.valueCount}</td>
                                <td>
                                    <select class="custom-select custom-select-sm mrf-display" name="mrf[{$row.id}][display]">
                                        {foreach $mrfDisplays as $value => $label}
                                            <option value="{$value}"{if $cfg->display === $value} selected{/if}>{$label}</option>
                                        {/foreach}
                                    </select>
                                </td>
                                <td>
                                    <select class="custom-select custom-select-sm mrf-slider-field mrf-range-field" name="mrf[{$row.id}][mode]">
                                        {foreach $mrfModes as $value => $label}
                                            <option value="{$value}"{if $cfg->mode === $value} selected{/if}>{$label}</option>
                                        {/foreach}
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm mrf-slider-field" maxlength="20"
                                           name="mrf[{$row.id}][unit]" value="{$cfg->unit|escape:'html'}"
                                           placeholder="{if $row.unit !== ''}auto: {$row.unit|escape:'html'}{else}automatisch{/if}">
                                </td>
                                <td>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm mrf-slider-field"
                                           name="mrf[{$row.id}][step]" value="{$cfg->step|string_format:'%g'}">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="mrf-any-field" name="mrf[{$row.id}][expanded]" value="1"{if $cfg->expanded} checked{/if}>
                                </td>
                                <td class="text-nowrap">
                                    {if $cfg->isSlider()}
                                        <button type="button" class="btn btn-link btn-sm p-0 mrf-toggle-values" data-target="#mrf-values-{$row.id}">
                                            Werte prüfen{if $row.invalid > 0} <span class="badge badge-danger">{$row.invalid}</span>{/if}
                                        </button>
                                    {/if}
                                </td>
                            </tr>
                            {if $cfg->isSlider()}
                                <tr class="mrf-values-row d-none" id="mrf-values-{$row.id}" data-mrf-parent="{$row.id}">
                                    <td colspan="8" class="bg-light">
                                        <p class="mb-2">
                                            {if $row.bounds !== null}
                                                Reglerskala gesamt: <strong>{$row.bounds.min} – {$row.bounds.max} {if $cfg->unit !== ''}{$cfg->unit|escape:'html'}{else}{$row.unit|escape:'html'}{/if}</strong>
                                                <span class="text-muted">(im Shop je Kategorie nur der Bereich der dort vorhandenen Artikel)</span>
                                            {else}
                                                <span class="text-danger">Keine auswertbaren Werte – der Regler wird nicht angezeigt.</span>
                                            {/if}
                                        </p>
                                        {if $row.invalid > 0}
                                            <div class="alert alert-danger py-2">
                                                {$row.invalid} Wert(e) enthalten keine Zahl. Artikel mit diesen Werten erscheinen nicht, sobald der Regler benutzt wird.
                                            </div>
                                        {/if}
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Wert (Standardsprache)</th>
                                                    <th class="text-right">{if $cfg->isSingleValue()}Zahl{else}von{/if}</th>
                                                    {if !$cfg->isSingleValue()}<th class="text-right">bis</th>{/if}
                                                    <th class="text-right">Artikel</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {foreach $row.values as $value}
                                                    <tr>
                                                        <td>{$value.text|escape:'html'}</td>
                                                        <td class="text-right">{$value.min|escape:'html'}</td>
                                                        {if !$cfg->isSingleValue()}<td class="text-right">{$value.max|escape:'html'}</td>{/if}
                                                        <td class="text-right">{$value.products}</td>
                                                        <td>
                                                            {if $value.ok}
                                                                <span class="text-success"><i class="fa fa-check"></i> erkannt</span>
                                                            {else}
                                                                <span class="text-danger"><i class="fa fa-exclamation-triangle"></i> keine Zahl</span>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                {foreachelse}
                                                    <tr><td colspan="5">Für dieses Merkmal sind keine Werte vorhanden.</td></tr>
                                                {/foreach}
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            {/if}
                        {foreachelse}
                            <tr><td colspan="8">Im Shop sind keine Merkmale vorhanden.</td></tr>
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
    </div>
</div>
<script>
(function () {
    var table = document.getElementById('mrf-table');
    if (!table) {
        return;
    }
    function sync(row) {
        var display = row.querySelector('.mrf-display').value;
        var slider  = display === 'slider_range' || display === 'slider_single';
        row.querySelectorAll('.mrf-slider-field, .mrf-any-field').forEach(function (field) {
            var off = field.classList.contains('mrf-any-field')
                ? display === 'default'
                : !slider || (field.classList.contains('mrf-range-field') && display === 'slider_single');
            field.disabled = off;
            field.style.opacity = off ? '0.35' : '';
        });
    }
    table.querySelectorAll('.mrf-row').forEach(function (row) {
        sync(row);
        row.querySelector('.mrf-display').addEventListener('change', function () { sync(row); });
    });
    // disabled fields are not submitted – enable them right before saving so values survive a type switch
    document.getElementById('mrf-form').addEventListener('submit', function () {
        table.querySelectorAll('.mrf-slider-field, .mrf-any-field').forEach(function (field) { field.disabled = false; });
    });
    table.querySelectorAll('.mrf-toggle-values').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelector(button.getAttribute('data-target')).classList.toggle('d-none');
        });
    });
    document.getElementById('mrf-search').addEventListener('input', function () {
        var term = this.value.trim().toLowerCase();
        table.querySelectorAll('.mrf-row').forEach(function (row) {
            var hit = term === '' || row.getAttribute('data-mrf-name').indexOf(term) !== -1;
            row.classList.toggle('d-none', !hit);
            var values = document.getElementById('mrf-values-' + row.getAttribute('data-mrf-id'));
            if (values && !hit) {
                values.classList.add('d-none');
            }
        });
    });
})();
</script>
