{* Formular einer Filtergruppe (Anlegen/Bearbeiten). $mrfGroup = Gruppe oder null, $mrfFormId = eindeutiger Präfix. *}
{if $mrfGroup !== null}
    {assign var=mrfSelectedChars value=$mrfGroup.characteristics}
    {assign var=mrfSelectedCats value=$mrfGroup.categories}
{else}
    {assign var=mrfSelectedChars value=[]}
    {assign var=mrfSelectedCats value=[]}
{/if}
<form method="post" class="modal-content mrf-group-form">
    {$jtl_token}
    <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
    <input type="hidden" name="mrf_action" value="group_save">
    <input type="hidden" name="mrf_group_id" value="{if $mrfGroup !== null}{$mrfGroup.id}{else}0{/if}">
    <div class="modal-header">
        <h2 class="modal-title">{if $mrfGroup !== null}Filtergruppe bearbeiten{else}Filtergruppe anlegen{/if}</h2>
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
            <i class="fal fa-times"></i>
        </button>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label for="mrf-group-name-{$mrfFormId}">Name der Gruppe</label>
            <input type="text" class="form-control" id="mrf-group-name-{$mrfFormId}" name="mrf_group_name" maxlength="100" required
                   placeholder="z. B. Snowboards" value="{if $mrfGroup !== null}{$mrfGroup.name|escape:'html'}{/if}">
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="mrf-section-label">Merkmale im Filter <span class="mrf-hint">– Reihenfolge per Ziehen</span></div>
                <ol class="mrf-sortable mrf-selected-list" data-empty="Noch keine Merkmale – rechts hinzufügen.">
                    {foreach $mrfSelectedChars as $cid}
                        {if isset($mrfCharacteristics[$cid])}
                            {assign var=char value=$mrfCharacteristics[$cid]}
                            <li class="mrf-sort-item" draggable="true" data-id="{$char.id}">
                                <span class="mrf-handle fal fa-grip-vertical" aria-hidden="true"></span>
                                <span class="mrf-sort-name">{$char.name|escape:'html'}</span>
                                {if $char.display !== ''}<span class="badge mrf-badge-other">{$char.display}</span>{/if}
                                <span class="mrf-sort-actions">
                                    <button type="button" class="btn btn-link btn-sm p-0 mrf-move" data-dir="-1" title="Nach oben"><span class="fal fa-chevron-up"></span></button>
                                    <button type="button" class="btn btn-link btn-sm p-0 mrf-move" data-dir="1" title="Nach unten"><span class="fal fa-chevron-down"></span></button>
                                    <button type="button" class="btn btn-link btn-sm p-0 mrf-remove-char" title="Entfernen"><span class="fal fa-times"></span></button>
                                </span>
                                <input type="hidden" name="mrf_group_characteristics[]" value="{$char.id}">
                            </li>
                        {/if}
                    {/foreach}
                </ol>
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <div class="mrf-section-label">Merkmal hinzufügen</div>
                <input type="search" class="form-control mb-2 mrf-char-search" placeholder="Merkmal suchen …" autocomplete="off">
                <div class="mrf-pick-list mrf-char-pool">
                    {foreach $mrfCharacteristics as $char}
                        <button type="button" class="mrf-pick-item mrf-add-char{if in_array($char.id, $mrfSelectedChars)} d-none{/if}"
                                data-id="{$char.id}" data-name="{$char.name|escape:'html'}" data-display="{$char.display}"
                                data-search="{$char.name|lower|escape:'html'}">
                            <span class="fal fa-plus mrf-plus"></span>
                            {$char.name|escape:'html'}
                            {if $char.display !== ''}<span class="badge mrf-badge-other">{$char.display}</span>{/if}
                        </button>
                    {/foreach}
                </div>
            </div>
        </div>

        <div class="mrf-section-label mt-4 d-flex align-items-center">
            <span class="mr-auto">Kategorien <span class="mrf-hint">– gilt nur für genau diese Kategorien, nicht für Unterkategorien</span></span>
            <span class="badge badge-primary mrf-cat-count">0</span>
        </div>
        <div class="d-flex mb-2">
            <input type="search" class="form-control mrf-cat-search mr-3" placeholder="Kategorie suchen …" autocomplete="off">
            <div class="custom-control custom-switch text-nowrap align-self-center">
                <input type="checkbox" class="custom-control-input mrf-cat-only" id="mrf-cat-only-{$mrfFormId}">
                <label class="custom-control-label" for="mrf-cat-only-{$mrfFormId}">nur ausgewählte</label>
            </div>
        </div>
        <div class="mrf-pick-list mrf-cat-list">
            {foreach $mrfCategories as $cat}
                {assign var=otherGroups value=[]}
                {if isset($mrfAssignedTo[$cat.id])}
                    {foreach $mrfAssignedTo[$cat.id] as $other}
                        {if $mrfGroup === null || $other.id !== $mrfGroup.id}{append var=otherGroups value=$other.name}{/if}
                    {/foreach}
                {/if}
                <label class="mrf-cat-item" style="padding-left: {$cat.level * 1.25 + 0.75}rem;" data-search="{$cat.path|lower|escape:'html'}">
                    <input type="checkbox" name="mrf_group_categories[]" value="{$cat.id}"{if in_array($cat.id, $mrfSelectedCats)} checked{/if}>
                    <span class="mrf-cat-name">{$cat.name|escape:'html'}</span>
                    <span class="mrf-cat-path d-none">{$cat.path|escape:'html'}</span>
                    {if $otherGroups|count > 0}<span class="badge mrf-badge-other" title="Weitere Filtergruppe an dieser Kategorie">auch: {foreach $otherGroups as $otherName}{if !$otherName@first}, {/if}{$otherName|escape:'html'}{/foreach}</span>{/if}
                    {if $cat.wawiAttribute !== ''}<span class="badge badge-warning" title="Wawi-Funktionsattribut merkmalfilter: {$cat.wawiAttribute|escape:'html'}">Wawi-Attribut</span>{/if}
                </label>
            {foreachelse}
                <div class="p-3 text-muted">Im Shop sind keine Kategorien vorhanden.</div>
            {/foreach}
            <div class="p-3 text-muted mrf-cat-none d-none">Keine Kategorie gefunden.</div>
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
