(function () {
    'use strict';
    var key = 'gojags-asrama-theme';
    var root = document.documentElement;
    var preference = null;
    var system = window.matchMedia('(prefers-color-scheme: dark)');
    try { preference = window.localStorage.getItem(key); } catch (_) {}

    function apply(dark) {
        root.classList.toggle('dark', dark);
        root.style.colorScheme = dark ? 'dark' : 'light';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', String(dark));
            button.setAttribute('aria-label', dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
            button.setAttribute('title', dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
        });
        window.dispatchEvent(new CustomEvent('asrama:themechange', { detail: { dark: dark } }));
    }

    apply(preference === 'dark' || (preference !== 'light' && system.matches));
    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element) || !event.target.closest('[data-theme-toggle]')) return;
        preference = root.classList.contains('dark') ? 'light' : 'dark';
        try { window.localStorage.setItem(key, preference); } catch (_) {}
        apply(preference === 'dark');
    });
    document.addEventListener('DOMContentLoaded', function () { apply(root.classList.contains('dark')); });
    window.addEventListener('asrama:sync-theme', function () { apply(root.classList.contains('dark')); });
    system.addEventListener('change', function (event) {
        if (preference !== 'light' && preference !== 'dark') apply(event.matches);
    });
    window.addEventListener('storage', function (event) {
        if (event.key !== key) return;
        preference = event.newValue;
        apply(preference === 'dark' || (preference !== 'light' && system.matches));
    });
})();
