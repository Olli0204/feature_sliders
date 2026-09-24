/* Merkmal-Bereichsfilter: initialises every .js-mrf-slider (sidebar box and AJAX-loaded filter modal). */
(function () {
    'use strict';
    if (window.mrfRangeFilter) {
        return;
    }

    function decimalsOf(step) {
        var parts = String(step).split('.');
        return parts.length > 1 ? parts[1].length : 0;
    }

    function parseNumber(text) {
        var value = parseFloat(String(text).replace(',', '.').replace(/[^0-9.]/g, ''));
        return isNaN(value) ? null : value;
    }

    function buildUrl(base, param, value) {
        var hashIdx = base.indexOf('#');
        if (hashIdx !== -1) {
            base = base.substring(0, hashIdx);
        }
        var parts  = base.split('?');
        var query  = (parts[1] || '').split('&').filter(function (pair) {
            return pair !== '' && decodeURIComponent(pair.split('=')[0]) !== param;
        });
        if (value !== null) {
            query.push(encodeURIComponent(param) + '=' + value);
        }
        return parts[0] + (query.length ? '?' + query.join('&') : '');
    }

    function navigate(root, url) {
        var inModal = root.closest('.js-collapse-filter') !== null;
        if (inModal && window.jQuery && window.jQuery.evo && typeof window.jQuery.evo.initFilters === 'function') {
            // same behaviour as NOVA's price slider inside the filter modal: reload the modal content
            window.jQuery.evo.initFilters(url);
        } else {
            window.location.href = url;
        }
    }

    function initSlider(root) {
        if (root.getAttribute('data-mrf-ready') === '1' || typeof window.noUiSlider === 'undefined') {
            return;
        }
        var track   = root.querySelector('.js-mrf-track');
        var inFrom  = root.querySelector('.js-mrf-from');
        var inTo    = root.querySelector('.js-mrf-to');
        var min     = parseFloat(root.getAttribute('data-mrf-min'));
        var max     = parseFloat(root.getAttribute('data-mrf-max'));
        var from    = parseFloat(root.getAttribute('data-mrf-from'));
        var to      = parseFloat(root.getAttribute('data-mrf-to'));
        var step    = parseFloat(root.getAttribute('data-mrf-step')) || 1;
        var param   = root.getAttribute('data-mrf-param');
        var baseUrl = (root.getAttribute('data-mrf-url') || window.location.href).replace(/&amp;/g, '&');
        var digits  = decimalsOf(root.getAttribute('data-mrf-step'));
        if (!track || !inFrom || !inTo || isNaN(min) || isNaN(max) || max <= min) {
            return;
        }
        root.setAttribute('data-mrf-ready', '1');

        var format = {
            to: function (value) { return Number(value).toFixed(digits); },
            from: function (value) { return Number(value); }
        };
        window.noUiSlider.create(track, {
            start: [from, to],
            connect: true,
            step: step,
            range: { min: min, max: max },
            format: format,
            handleAttributes: [
                { 'aria-label': inFrom.getAttribute('aria-label') || 'lower' },
                { 'aria-label': inTo.getAttribute('aria-label') || 'upper' }
            ]
        });

        function apply(values) {
            var lo = parseFloat(values[0]);
            var hi = parseFloat(values[1]);
            if (lo === from && hi === to) {
                return;
            }
            var full = lo <= min && hi >= max;
            navigate(root, buildUrl(baseUrl, param, full ? null : format.to(lo) + '_' + format.to(hi)));
        }

        track.noUiSlider.on('update', function (values) {
            inFrom.value = values[0];
            inTo.value   = values[1];
        });
        track.noUiSlider.on('change', function (values) {
            apply(values);
        });

        function onInput() {
            var lo = parseNumber(inFrom.value);
            var hi = parseNumber(inTo.value);
            lo = lo === null ? min : Math.max(min, Math.min(lo, max));
            hi = hi === null ? max : Math.max(min, Math.min(hi, max));
            if (lo > hi) {
                var swap = lo;
                lo = hi;
                hi = swap;
            }
            track.noUiSlider.set([lo, hi]);
            apply(track.noUiSlider.get());
        }
        [inFrom, inTo].forEach(function (input) {
            input.addEventListener('change', onInput);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.blur();
                }
            });
        });
    }

    function initAll() {
        var nodes = document.querySelectorAll('.js-mrf-slider');
        for (var i = 0; i < nodes.length; i++) {
            initSlider(nodes[i]);
        }
    }

    window.mrfRangeFilter = { init: initAll, buildUrl: buildUrl };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
    // NOVA loads the filter modal via AJAX ($.evo.initFilters) – initialise new sliders afterwards
    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(function () {
            window.setTimeout(initAll, 0);
        });
    }
})();
