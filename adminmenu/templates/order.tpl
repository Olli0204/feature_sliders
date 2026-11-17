{* Reiter „Merkmale“, Bereich „Reihenfolge der Filter“: Standard-Reihenfolge aller Merkmalfilter auf Kategorieseiten
   (gilt auch in Kategorien mit Filtergruppe). $mrfOrder = alle Merkmale, gespeicherte Reihenfolge zuerst. *}
<style>
    .mrf-order .mrf-order-tools { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: .75rem; }
    .mrf-order .mrf-order-search { max-width: 18rem; }
    .mrf-order .mrf-order-state { font-size: .8125rem; }
    .mrf-order .mrf-order-list { list-style: none; margin: 0; padding: 0; max-height: 30rem; overflow-y: auto; border: 1px solid var(--border-color, #e4e9f2); border-radius: .25rem; counter-reset: mrfo; }
    .mrf-order .mrf-order-item { display: flex; align-items: center; gap: .75rem; padding: .45rem .75rem; border-bottom: 1px solid var(--border-color, #e4e9f2); background: var(--card-bg, #fff); counter-increment: mrfo; }
    .mrf-order .mrf-order-item:last-child { border-bottom: 0; }
    .mrf-order .mrf-order-item::before { content: counter(mrfo) "."; width: 1.75rem; flex: 0 0 1.75rem; font-size: .8125rem; opacity: .6; text-align: right; }
    .mrf-order .mrf-order-item.mrf-dragging { opacity: .4; }
    .mrf-order .mrf-order-item.mrf-moved { background: var(--table-accent-color, #f5f7fa); }
    .mrf-order .mrf-handle { cursor: grab; opacity: .55; }
    .mrf-order .mrf-order-name { flex: 1; min-width: 0; }
    .mrf-order .mrf-order-name strong { font-weight: 600; }
    .mrf-order .mrf-order-samples { display: block; font-size: .75rem; opacity: .65; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mrf-order .mrf-order-badge { font-size: .75rem; padding: .05rem .45rem; border: 1px solid var(--border-color, #e4e9f2); border-radius: .75rem; background: var(--table-accent-color, #f5f7fa); white-space: nowrap; }
    .mrf-order .mrf-order-actions { display: flex; gap: .125rem; }
    .mrf-order .mrf-order-actions .btn { padding: .125rem .4rem; }
    .mrf-order .mrf-order-none { display: none; padding: .75rem; opacity: .7; }
</style>

<div class="card mrf-order mt-4">
    <div class="card-header">
        <div class="subheading1">Reihenfolge der Filter</div>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            In dieser Reihenfolge erscheinen die Merkmalfilter auf den Kategorieseiten – in allen Kategorien, auch in denen mit
            Filtergruppe (die Gruppe legt dann nur fest, welche Merkmale erscheinen). Ziehen oder mit den Pfeilen verschieben,
            danach speichern. Der Preisfilter ist eine eigene Box und wird in der Boxenverwaltung einsortiert.
        </p>
        <form method="post" id="mrf-order-form">
            {$jtl_token}
            <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
            <input type="hidden" name="mrf_action" value="order_save">
            <div class="mrf-order-tools">
                <input type="search" class="form-control mrf-order-search" placeholder="Merkmal suchen …" autocomplete="off" aria-label="Merkmal suchen">
                <span class="mrf-order-state text-muted ml-auto">
                    {if $mrfOrderCustom}Eigene Reihenfolge aktiv{else}Noch keine eigene Reihenfolge – es gilt die Wawi-Reihenfolge{/if}
                </span>
            </div>
            <ol class="mrf-order-list" id="mrf-order-list">
                {foreach $mrfOrder as $item}
                    <li class="mrf-order-item" draggable="true" data-search="{$item.name|lower|escape:'html'}">
                        <span class="fal fa-grip-vertical mrf-handle" title="Ziehen zum Verschieben"></span>
                        <input type="hidden" name="mrf_order[]" value="{$item.id}">
                        <span class="mrf-order-name">
                            <strong>{$item.name|escape:'html'}</strong>
                            {if $item.samples|count > 0}<span class="mrf-order-samples">{foreach $item.samples as $sample}{if !$sample@first} · {/if}{$sample|escape:'html'}{/foreach}</span>{/if}
                        </span>
                        {if $item.display !== ''}<span class="mrf-order-badge">{$item.display}</span>{/if}
                        <span class="mrf-order-actions">
                            <button type="button" class="btn btn-link mrf-order-top" title="Ganz nach oben"><span class="fal fa-arrow-to-top"></span><span class="sr-only">Ganz nach oben</span></button>
                            <button type="button" class="btn btn-link mrf-order-up" title="Eins nach oben"><span class="fal fa-arrow-up"></span><span class="sr-only">Nach oben</span></button>
                            <button type="button" class="btn btn-link mrf-order-down" title="Eins nach unten"><span class="fal fa-arrow-down"></span><span class="sr-only">Nach unten</span></button>
                        </span>
                    </li>
                {/foreach}
            </ol>
            <div class="mrf-order-none">Kein Merkmal gefunden.</div>
        </form>
        <form method="post" id="mrf-order-reset-form" class="d-none">
            {$jtl_token}
            <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
            <input type="hidden" name="mrf_action" value="order_reset">
        </form>
    </div>
    <div class="card-footer save-wrapper">
        <div class="row">
            <div class="col-sm-6 col-xl-auto submit">
                <button type="submit" class="btn btn-outline-primary btn-block" form="mrf-order-reset-form"
                        id="mrf-order-reset"{if !$mrfOrderCustom} disabled{/if}>
                    <i class="fal fa-undo"></i> Wawi-Reihenfolge wiederherstellen
                </button>
            </div>
            <div class="ml-auto col-sm-6 col-xl-auto submit">
                <button type="submit" class="btn btn-primary btn-block" form="mrf-order-form" id="mrf-order-save">
                    <i class="fal fa-save"></i> Reihenfolge speichern
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var list = document.getElementById('mrf-order-list');
    if (!list) {
        return;
    }
    var state   = document.querySelector('.mrf-order .mrf-order-state');
    var initial = '';
    function order() {
        return Array.prototype.map.call(list.querySelectorAll('input[name="mrf_order[]"]'), function (i) { return i.value; }).join(',');
    }
    initial = order();
    function changed(item) {
        if (item) {
            item.classList.add('mrf-moved');
        }
        var dirty = order() !== initial;
        state.textContent = dirty ? 'Ungespeicherte Änderungen – bitte „Reihenfolge speichern“' : state.getAttribute('data-initial');
        state.classList.toggle('text-warning', dirty);
        state.classList.toggle('text-muted', !dirty);
    }
    state.setAttribute('data-initial', state.textContent.trim());

    /* drag & drop */
    var dragged = null;
    list.addEventListener('dragstart', function (e) {
        dragged = e.target.closest('.mrf-order-item');
        if (!dragged) {
            return;
        }
        dragged.classList.add('mrf-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    });
    list.addEventListener('dragover', function (e) {
        var over = e.target.closest('.mrf-order-item');
        if (!dragged || !over || over === dragged) {
            return;
        }
        e.preventDefault();
        var box = over.getBoundingClientRect();
        list.insertBefore(dragged, e.clientY < box.top + box.height / 2 ? over : over.nextSibling);
    });
    list.addEventListener('drop', function (e) {
        // Firefox would otherwise open the dragged text as URL
        e.preventDefault();
    });
    list.addEventListener('dragend', function () {
        if (dragged) {
            dragged.classList.remove('mrf-dragging');
            changed(dragged);
            dragged = null;
        }
    });

    /* arrow buttons */
    list.addEventListener('click', function (e) {
        var btn  = e.target.closest('button');
        var item = btn ? btn.closest('.mrf-order-item') : null;
        if (!item) {
            return;
        }
        if (btn.classList.contains('mrf-order-top')) {
            list.insertBefore(item, list.firstElementChild);
            list.scrollTop = 0;
        } else if (btn.classList.contains('mrf-order-up') && item.previousElementSibling) {
            list.insertBefore(item, item.previousElementSibling);
        } else if (btn.classList.contains('mrf-order-down') && item.nextElementSibling) {
            list.insertBefore(item.nextElementSibling, item);
        }
        changed(item);
        btn.focus();
    });

    /* search */
    var search = document.querySelector('.mrf-order-search');
    var none   = document.querySelector('.mrf-order .mrf-order-none');
    search.addEventListener('input', function () {
        var q    = search.value.trim().toLowerCase();
        var hits = 0;
        list.querySelectorAll('.mrf-order-item').forEach(function (item) {
            var show = q === '' || item.getAttribute('data-search').indexOf(q) !== -1;
            item.style.display = show ? '' : 'none';
            hits += show ? 1 : 0;
        });
        none.style.display = hits === 0 ? 'block' : 'none';
    });
    /* Enter in the search field must not submit the order form */
    search.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    document.getElementById('mrf-order-reset').addEventListener('click', function (e) {
        if (!window.confirm('Eigene Reihenfolge löschen? Danach gilt wieder die Wawi-Reihenfolge bzw. die Reihenfolge der Filtergruppen.')) {
            e.preventDefault();
        }
    });
})();
</script>
