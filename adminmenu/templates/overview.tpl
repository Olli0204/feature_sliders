<div class="card">
    <div class="card-header">
        <div class="subheading1">Werteübersicht</div>
    </div>
    <div class="card-body">
        {if !$mrfSettings->active}
            <div class="alert alert-warning">Der Filter ist in den Einstellungen deaktiviert.</div>
        {/if}
        {if $mrfSettings->characteristicID <= 0}
            <div class="alert alert-info">
                Bitte zuerst unter <strong>Einstellungen</strong> das Merkmal auswählen, dessen Werte als Bereich
                gepflegt sind (z. B. Körpergewicht mit Werten wie „35 - 55 kg“).
            </div>
        {else}
            <p>
                Merkmal: <strong>{$mrfCharacteristicName|escape:'html'}</strong> (ID {$mrfSettings->characteristicID})
                · Treffer-Logik: <strong>{if $mrfSettings->mode === 'contains'}Artikel-Bereich enthält die Auswahl{elseif $mrfSettings->mode === 'within'}Artikel-Bereich liegt in der Auswahl{else}Bereiche überschneiden sich{/if}</strong>
                {if $mrfBounds !== null}
                    · Reglerskala gesamt: <strong>{$mrfBounds.min} – {$mrfBounds.max} {$mrfSettings->unit|escape:'html'}</strong>
                {/if}
            </p>
            {if $mrfInvalid > 0}
                <div class="alert alert-danger">
                    {$mrfInvalid} Wert(e) enthalten keine Zahl und werden vom Schieberegler ignoriert.
                    Artikel mit diesen Werten erscheinen nicht, sobald der Filter gesetzt ist.
                </div>
            {/if}
            <p class="text-muted small">
                Erkannte Schreibweisen: „35 - 55 kg“, „35–55kg“, „35 bis 55 kg“, „35,5 - 55 kg“ (Bereich),
                „bis 55 kg“ / „&lt; 55 kg“ (nach unten offen), „ab 80 kg“ / „80+ kg“ (nach oben offen),
                „55 kg“ (Einzelwert). Der Regler zeigt in jeder Kategorie nur die Skala der dort vorhandenen Werte.
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Wert (Standardsprache)</th>
                            <th class="text-right">von</th>
                            <th class="text-right">bis</th>
                            <th class="text-right">Artikel</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $mrfRows as $row}
                            <tr>
                                <td>{$row.value|escape:'html'}</td>
                                <td class="text-right">{$row.min|escape:'html'}</td>
                                <td class="text-right">{$row.max|escape:'html'}</td>
                                <td class="text-right">{$row.products}</td>
                                <td>
                                    {if $row.ok}
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
            </div>
            <hr>
            <p class="mb-0">
                <strong>Einbindung:</strong> Desktop-Seitenleiste über <em>Darstellung → Boxen</em>, Seitentyp
                <em>Artikelliste</em>, Box „Merkmal-Bereichsfilter (Schieberegler)“ in die linke Seitenleiste setzen.
                Im mobilen Filter-Dialog erscheint der Regler automatisch.
            </p>
        {/if}
    </div>
</div>
