{* Auswahlkacheln für die Darstellung; $mrfSelected = aktueller Wert, $mrfTileId = eindeutiger Präfix je Dialog. *}
<div class="mrf-tiles" role="radiogroup" aria-label="Darstellung">
    <label class="mrf-tile{if $mrfSelected === 'slider_range'} is-selected{/if}" for="mrf-tile-{$mrfTileId}-range">
        <input type="radio" id="mrf-tile-{$mrfTileId}-range" name="mrf[display]" value="slider_range" required{if $mrfSelected === 'slider_range'} checked{/if}>
        <span class="mrf-tile-art"><span class="t"></span></span>
        <span class="mrf-tile-title">Regler: Bereich auf Bereich</span>
        <span class="mrf-tile-text">Werte sind Bereiche, z. B. „35 - 55 kg“</span>
    </label>
    <label class="mrf-tile{if $mrfSelected === 'slider_single'} is-selected{/if}" for="mrf-tile-{$mrfTileId}-single">
        <input type="radio" id="mrf-tile-{$mrfTileId}-single" name="mrf[display]" value="slider_single" required{if $mrfSelected === 'slider_single'} checked{/if}>
        <span class="mrf-tile-art"><span class="t single"></span></span>
        <span class="mrf-tile-title">Regler: Bereich auf Einzelwerte</span>
        <span class="mrf-tile-text">Werte sind Zahlen, z. B. „154 cm“</span>
    </label>
    <label class="mrf-tile{if $mrfSelected === 'buttons'} is-selected{/if}" for="mrf-tile-{$mrfTileId}-buttons">
        <input type="radio" id="mrf-tile-{$mrfTileId}-buttons" name="mrf[display]" value="buttons" required{if $mrfSelected === 'buttons'} checked{/if}>
        <span class="mrf-tile-art"><span class="b"><i></i><i></i><i></i></span></span>
        <span class="mrf-tile-title">Boxen</span>
        <span class="mrf-tile-text">Werte als Buttons statt Checkliste</span>
    </label>
</div>
