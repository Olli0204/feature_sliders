{* Tab „Filtergruppen“: welche Merkmale (in welcher Reihenfolge) in welchen Kategorien als Filter erscheinen. *}
<style>
    /* Farben aus JTLs Theme-Variablen (hell/dunkel, custom.css). */
    .mrf-groups .mrf-chip { display: inline-block; margin: 0 .25rem .25rem 0; padding: .125rem .5rem; font-size: .8125rem; background: var(--table-accent-color, #f5f7fa); border: 1px solid var(--border-color, #e4e9f2); border-radius: .75rem; white-space: nowrap; }
    .mrf-groups .mrf-muted-list { font-size: .8125rem; }
    .mrf-groups .mrf-muted-list .mrf-path { opacity: .75; }
    .mrf-groups .mrf-drag-cell { width: 2rem; cursor: grab; opacity: .6; }
    .mrf-groups tr.mrf-dragging, .mrf-sort-item.mrf-dragging { opacity: .4; }
    .mrf-groups .mrf-empty { padding: 2.5rem 1rem; text-align: center; }
    .mrf-groups .mrf-empty-icon { display: block; margin: 0 auto .75rem; font-size: 2rem; line-height: 1; opacity: .35; }
    .mrf-section-label { font-weight: 600; margin-bottom: .5rem; }
    .mrf-section-label .mrf-hint { font-weight: 400; opacity: .7; }
    .mrf-sortable { list-style: none; margin: 0; padding: 0; min-height: 3rem; border: 1px solid var(--border-color, #e4e9f2); border-radius: .25rem; max-height: 20rem; overflow-y: auto; counter-reset: mrf; }
    .mrf-sortable:empty::before { content: attr(data-empty); display: block; padding: .75rem; opacity: .7; font-size: .875rem; }
    .mrf-sort-item { display: flex; align-items: center; gap: .5rem; padding: .4rem .75rem; border-bottom: 1px solid var(--border-color, #e4e9f2); background: var(--card-bg, #fff); counter-increment: mrf; }
    .mrf-sort-item:last-child { border-bottom: 0; }
    .mrf-sort-item::before { content: counter(mrf) "."; width: 1.5rem; opacity: .6; font-size: .8125rem; }
    .mrf-sort-item .mrf-handle { cursor: grab; opacity: .6; }
    .mrf-sort-item .mrf-sort-name { flex: 1; }
    .mrf-sort-item .mrf-sort-actions { display: flex; gap: .5rem; }
    .mrf-pick-list { max-height: 20rem; overflow-y: auto; border: 1px solid var(--border-color, #e4e9f2); border-radius: .25rem; }
    .mrf-pick-item { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .4rem .75rem; border: 0; border-bottom: 1px solid var(--border-color, #e4e9f2); background: var(--card-bg, #fff); text-align: left; color: var(--body-color, #435a6b); }
    .mrf-pick-item:hover { background: var(--table-accent-color, #f5f7fa); }
    .mrf-pick-item .mrf-plus { opacity: .6; }
    .mrf-cat-list { max-height: 22rem; }
    .mrf-cat-item { display: flex; align-items: center; gap: .5rem; margin: 0; padding-top: .3rem; padding-bottom: .3rem; padding-right: .75rem; border-bottom: 1px solid var(--border-color, #e4e9f2); cursor: pointer; font-weight: 400; }
    .mrf-cat-item:hover { background: var(--table-accent-color, #f5f7fa); }
    .mrf-cat-item .mrf-cat-name { flex: 0 1 auto; }
    .mrf-cat-list.is-searching .mrf-cat-item { padding-left: .75rem !important; }
    .mrf-cat-list.is-searching .mrf-cat-name { display: none; }
    .mrf-cat-list.is-searching .mrf-cat-path { display: inline !important; opacity: 1 !important; }
    .mrf-cat-item .badge { font-weight: 400; }
    .mrf-badge-other { background: var(--table-accent-color, #f5f7fa); color: var(--body-color, #435a6b); border: 1px solid var(--border-color, #e4e9f2); }
</style>

<div class="mrf-groups">
    {if $mrfMessage !== null}
        <div class="alert alert-{$mrfMessage.type}">{$mrfMessage.text|escape:'html'}</div>
    {/if}

    <div class="card">
        <div class="card-header">
            <div class="subheading1">Filtergruppen</div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Eine Filtergruppe legt fest, welche Merkmale in einer Kategorie als Filter erscheinen – in genau der Reihenfolge der Gruppe.
                Sie gilt nur für die gewählten Kategorien, nicht für deren Unterkategorien. Hat eine Kategorie mehrere Gruppen, kommen
                die Merkmale in der Reihenfolge der Gruppen hier in der Liste. Kategorien ohne Gruppe verhalten sich wie bisher
                (Wawi-Funktionsattribut „merkmalfilter“ oder alle Merkmale), ebenso Suche und Herstellerseiten.
            </p>
            {if $mrfGroups|count === 0}
                <div class="mrf-empty">
                    <span class="mrf-empty-icon"><span class="fal fa-layer-group"></span></span>
                    Noch keine Filtergruppen angelegt – unten über „Gruppe anlegen“ starten.
                </div>
            {else}
                <form method="post" id="mrf-group-order-form">
                    {$jtl_token}
                    <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                    <input type="hidden" name="mrf_action" value="group_reorder">
                    <div class="table-responsive">
                        <table class="list table table-align-top mb-0">
                            <thead>
                                <tr>
                                    <th class="mrf-drag-cell"><span class="sr-only">Reihenfolge</span></th>
                                    <th>Gruppe</th>
                                    <th>Merkmale</th>
                                    <th>Kategorien</th>
                                    <th class="text-center" style="width: 7rem;">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody id="mrf-group-rows">
                                {foreach $mrfGroups as $group}
                                    <tr draggable="true" data-id="{$group.id}">
                                        <td class="mrf-drag-cell" title="Reihenfolge per Ziehen ändern">
                                            <span class="fal fa-grip-vertical"></span>
                                            <input type="hidden" name="mrf_group_order[]" value="{$group.id}">
                                        </td>
                                        <td><strong>{$group.name|escape:'html'}</strong></td>
                                        <td>
                                            {foreach $group.characteristicNames as $name}<span class="mrf-chip">{$name|escape:'html'}</span>{foreachelse}<span class="text-warning small">keine Merkmale – in diesen Kategorien erscheint kein Merkmalfilter</span>{/foreach}
                                        </td>
                                        <td class="mrf-muted-list">
                                            {if $group.categoryPaths|count === 0}
                                                <span class="text-warning">keiner Kategorie zugewiesen</span>
                                            {else}
                                                <strong>{$group.categoryPaths|count} {if $group.categoryPaths|count === 1}Kategorie{else}Kategorien{/if}</strong><br>
                                                <span class="mrf-path">{foreach $group.categoryPaths as $path}{if $path@iteration <= 3}{if !$path@first}<br>{/if}{$path|escape:'html'}{/if}{/foreach}</span>
                                                {if $group.moreCategories > 0}<br>… und {$group.moreCategories} weitere{/if}
                                            {/if}
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-link px-2" data-toggle="modal" data-target="#mrf-group-{$group.id}" title="Bearbeiten">
                                                    <span class="icon-hover">
                                                        <span class="fal fa-edit"></span>
                                                        <span class="fas fa-edit"></span>
                                                    </span>
                                                </button>
                                                <button type="submit" class="btn btn-link px-2 mrf-group-remove" form="mrf-group-remove-{$group.id}"
                                                        data-name="{$group.name|escape:'html'}" title="Löschen">
                                                    <span class="icon-hover">
                                                        <span class="fal fa-trash-alt"></span>
                                                        <span class="fas fa-trash-alt"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </form>
                {foreach $mrfGroups as $group}
                    <form method="post" id="mrf-group-remove-{$group.id}" class="d-none">
                        {$jtl_token}
                        <input type="hidden" name="kPluginAdminMenu" value="{$mrfMenuID}">
                        <input type="hidden" name="mrf_action" value="group_remove">
                        <input type="hidden" name="mrf_group_id" value="{$group.id}">
                    </form>
                {/foreach}
            {/if}
        </div>
        <div class="card-footer save-wrapper">
            <div class="row">
                <div class="ml-auto col-sm-6 col-xl-auto submit">
                    <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#mrf-group-new">
                        <i class="fa fa-plus"></i> Gruppe anlegen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {if $mrfWawiCategories|count > 0}
        <div class="card">
            <div class="card-header"><div class="subheading1">Wawi-Funktionsattribut „merkmalfilter“</div></div>
            <div class="card-body">
                <p class="text-muted">
                    Diese Kategorien haben in der Wawi noch das Funktionsattribut. Wo eine Filtergruppe zugewiesen ist, hat die Gruppe
                    Vorrang – das Attribut kann dort in der Wawi entfernt werden.
                </p>
                <div class="table-responsive">
                    <table class="list table table-sm mb-0">
                        <thead><tr><th>Kategorie</th><th>Wawi-Attribut</th><th>Filtergruppe</th></tr></thead>
                        <tbody>
                            {foreach $mrfWawiCategories as $cat}
                                <tr>
                                    <td>{$cat.path|escape:'html'}</td>
                                    <td class="mrf-muted-list">{$cat.wawiAttribute|escape:'html'}</td>
                                    <td>
                                        {if $cat.hasGroup}
                                            <span class="text-success"><span class="fal fa-check"></span> zugewiesen – Attribut kann weg</span>
                                        {else}
                                            <span class="text-muted">keine – Attribut noch aktiv</span>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    {/if}

    <div class="modal fade" id="mrf-group-new" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            {include file=$mrfGroupFormTpl mrfGroup=null mrfFormId="new"}
        </div>
    </div>
    {foreach $mrfGroups as $group}
        <div class="modal fade" id="mrf-group-{$group.id}" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                {include file=$mrfGroupFormTpl mrfGroup=$group mrfFormId=$group.id}
            </div>
        </div>
    {/foreach}
</div>

<script>
(function () {
    'use strict';
    var root = document.querySelector('.mrf-groups');
    if (!root) {
        return;
    }

    /* minimal drag & drop sorting for list items / table rows (plus ↑↓ buttons in the dialogs) */
    function makeSortable(container, itemSelector, onDrop) {
        var dragged = null;
        container.addEventListener('dragstart', function (e) {
            dragged = e.target.closest(itemSelector);
            if (!dragged) {
                return;
            }
            dragged.classList.add('mrf-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragged.getAttribute('data-id') || '');
        });
        container.addEventListener('dragover', function (e) {
            var over = e.target.closest(itemSelector);
            if (!dragged || !over || over === dragged || over.parentNode !== dragged.parentNode) {
                return;
            }
            e.preventDefault();
            var box    = over.getBoundingClientRect();
            var before = e.clientY < box.top + box.height / 2;
            over.parentNode.insertBefore(dragged, before ? over : over.nextSibling);
        });
        container.addEventListener('drop', function (e) {
            // Firefox would otherwise open the dragged text as URL
            e.preventDefault();
        });
        container.addEventListener('dragend', function () {
            if (dragged) {
                dragged.classList.remove('mrf-dragging');
                dragged = null;
                if (onDrop) {
                    onDrop();
                }
            }
        });
    }

    /* group order in the overview → saved right after dropping */
    var rows = document.getElementById('mrf-group-rows');
    if (rows) {
        var initial = Array.prototype.map.call(rows.children, function (tr) { return tr.getAttribute('data-id'); }).join(',');
        makeSortable(rows, 'tr', function () {
            var now = Array.prototype.map.call(rows.children, function (tr) { return tr.getAttribute('data-id'); }).join(',');
            if (now !== initial) {
                document.getElementById('mrf-group-order-form').submit();
            }
        });
    }
    root.querySelectorAll('.mrf-group-remove').forEach(function (button) {
        button.addEventListener('click', function (e) {
            var question = 'Filtergruppe „' + button.getAttribute('data-name') + '“ löschen? Die Kategorien verhalten sich danach wie ohne Gruppe.';
            if (!window.confirm(question)) {
                e.preventDefault();
            }
        });
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    root.querySelectorAll('.mrf-group-form').forEach(function (form) {
        var selected = form.querySelector('.mrf-selected-list');
        var pool     = form.querySelector('.mrf-char-pool');
        makeSortable(selected, '.mrf-sort-item');

        function poolItem(id) {
            return pool.querySelector('.mrf-add-char[data-id="' + id + '"]');
        }
        function addCharacteristic(button) {
            var id      = button.getAttribute('data-id');
            var display = button.getAttribute('data-display');
            var li      = document.createElement('li');
            li.className = 'mrf-sort-item';
            li.setAttribute('draggable', 'true');
            li.setAttribute('data-id', id);
            li.innerHTML = '<span class="mrf-handle fal fa-grip-vertical" aria-hidden="true"></span>'
                + '<span class="mrf-sort-name">' + escapeHtml(button.getAttribute('data-name')) + '</span>'
                + (display ? '<span class="badge mrf-badge-other">' + escapeHtml(display) + '</span>' : '')
                + '<span class="mrf-sort-actions">'
                + '<button type="button" class="btn btn-link btn-sm p-0 mrf-move" data-dir="-1" title="Nach oben"><span class="fal fa-chevron-up"></span></button>'
                + '<button type="button" class="btn btn-link btn-sm p-0 mrf-move" data-dir="1" title="Nach unten"><span class="fal fa-chevron-down"></span></button>'
                + '<button type="button" class="btn btn-link btn-sm p-0 mrf-remove-char" title="Entfernen"><span class="fal fa-times"></span></button>'
                + '</span><input type="hidden" name="mrf_group_characteristics[]" value="' + escapeHtml(id) + '">';
            selected.appendChild(li);
            button.classList.add('d-none');
        }
        pool.addEventListener('click', function (e) {
            var button = e.target.closest('.mrf-add-char');
            if (button) {
                addCharacteristic(button);
            }
        });
        selected.addEventListener('click', function (e) {
            var item = e.target.closest('.mrf-sort-item');
            if (!item) {
                return;
            }
            if (e.target.closest('.mrf-remove-char')) {
                var back = poolItem(item.getAttribute('data-id'));
                if (back) {
                    back.classList.remove('d-none');
                }
                item.remove();
            } else if (e.target.closest('.mrf-move')) {
                var dir = parseInt(e.target.closest('.mrf-move').getAttribute('data-dir'), 10);
                if (dir < 0 && item.previousElementSibling) {
                    selected.insertBefore(item, item.previousElementSibling);
                } else if (dir > 0 && item.nextElementSibling) {
                    selected.insertBefore(item.nextElementSibling, item);
                }
            }
        });
        form.querySelector('.mrf-char-search').addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            pool.querySelectorAll('.mrf-add-char').forEach(function (button) {
                var inGroup = selected.querySelector('[data-id="' + button.getAttribute('data-id') + '"]') !== null;
                var hit     = term === '' || button.getAttribute('data-search').indexOf(term) !== -1;
                button.classList.toggle('d-none', inGroup || !hit);
            });
        });

        var catList   = form.querySelector('.mrf-cat-list');
        var catSearch = form.querySelector('.mrf-cat-search');
        var onlySel   = form.querySelector('.mrf-cat-only');
        var counter   = form.querySelector('.mrf-cat-count');
        function filterCategories() {
            var term  = catSearch.value.trim().toLowerCase();
            var shown = 0;
            catList.classList.toggle('is-searching', term !== '');
            catList.querySelectorAll('.mrf-cat-item').forEach(function (item) {
                var checked = item.querySelector('input').checked;
                var hit     = (term === '' || item.getAttribute('data-search').indexOf(term) !== -1) && (!onlySel.checked || checked);
                item.classList.toggle('d-none', !hit);
                shown += hit ? 1 : 0;
            });
            catList.querySelector('.mrf-cat-none').classList.toggle('d-none', shown > 0);
            counter.textContent = catList.querySelectorAll('.mrf-cat-item input:checked').length + ' ausgewählt';
        }
        catSearch.addEventListener('input', filterCategories);
        onlySel.addEventListener('change', filterCategories);
        catList.addEventListener('change', filterCategories);
        filterCategories();
    });

    {if $mrfOpenEdit > 0}
    if (window.jQuery) {
        window.jQuery(function () {
            window.jQuery('#mrf-group-{$mrfOpenEdit}').modal('show');
        });
    }
    {/if}
})();
</script>
