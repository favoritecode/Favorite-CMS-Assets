/* Favorite Web Official Theme: mobile navigation, account menu, dark mode toggle, scroll state, and image fallbacks. No external dependencies. */
(function () {
    'use strict';
    var doc = document;

    function init() {
        // 1. Mobile Navigation
        var toggle = doc.getElementById('mobile-nav-btn');
        var panel = doc.getElementById('header-nav-wrap');

        if (toggle && panel) {
            var setOpen = function (open, focusToggle) {
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
                panel.classList.toggle('is-open', open);
                if (!open && focusToggle) { toggle.focus(); }
            };
            toggle.addEventListener('click', function () {
                setOpen(toggle.getAttribute('aria-expanded') !== 'true');
            });
            doc.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && panel.classList.contains('is-open')) { setOpen(false, true); }
            });
            panel.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('a[href]') && panel.classList.contains('is-open')) { setOpen(false); }
            });
            if (window.matchMedia) {
                var desktop = window.matchMedia('(min-width: 1024px)');
                var onChange = function (q) { if (q.matches) { setOpen(false); } };
                if (desktop.addEventListener) { desktop.addEventListener('change', onChange); } else if (desktop.addListener) { desktop.addListener(onChange); }
            }
        }

        // 2. Dark / Light Mode Toggle
        var themeBtn = doc.getElementById('theme-toggle-btn') || doc.getElementById('fw-theme-toggle');
        if (themeBtn) {
            var updateThemeBtnState = function (theme) {
                var isDark = theme === 'dark';
                themeBtn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                themeBtn.setAttribute('title', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            };

            var getCurrentTheme = function () {
                var attr = doc.documentElement.getAttribute('data-theme');
                if (attr) { return attr; }
                var saved = localStorage.getItem('fw_theme_pref');
                if (saved) { return saved; }
                return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
            };

            updateThemeBtnState(getCurrentTheme());

            themeBtn.addEventListener('click', function () {
                var current = getCurrentTheme();
                var next = current === 'dark' ? 'light' : 'dark';
                doc.documentElement.setAttribute('data-theme', next);
                try {
                    localStorage.setItem('fw_theme_pref', next);
                } catch (e) {}
                updateThemeBtnState(next);
            });
        }

        // 3. Sticky Header Scroll Watcher
        var header = doc.getElementById('site-header');
        if (header) {
            var onScroll = function () {
                var scrolled = (window.scrollY || doc.documentElement.scrollTop || 0) > 15;
                header.classList.toggle('is-scrolled', scrolled);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        // 4. Broken Image Fallback
        var wrappers = '.post-card__media, .entry-media, .featured-post-card__media, .widget-recent-posts__thumb, .fw-product-card__media';
        doc.querySelectorAll('.post-card__media img, .entry-media img, .featured-post-card__media img, .widget-recent-posts__thumb img, .fw-product-card__media img').forEach(function (img) {
            var hide = function () { var w = img.closest(wrappers); if (w) { w.classList.add('is-broken'); } };
            if (img.complete && img.getAttribute('src') && img.naturalWidth === 0) { hide(); }
            img.addEventListener('error', hide);
        });

        // 5. Account Menu Dropdown
        doc.querySelectorAll('.cms-account-menu').forEach(function (menu) {
            var trigger = menu.querySelector('.cms-account-trigger');
            if (!trigger || !menu.querySelector('.cms-account-dropdown')) { return; }
            var setMenu = function (open, focusTrigger) {
                menu.classList.toggle('is-open', open);
                trigger.setAttribute('aria-expanded', String(open));
                if (!open && focusTrigger) { trigger.focus(); }
            };
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = !menu.classList.contains('is-open');
                doc.querySelectorAll('.cms-account-menu.is-open').forEach(function (other) {
                    if (other === menu) { return; }
                    other.classList.remove('is-open');
                    var otherTrigger = other.querySelector('.cms-account-trigger');
                    if (otherTrigger) { otherTrigger.setAttribute('aria-expanded', 'false'); }
                });
                setMenu(open);
            });
            doc.addEventListener('click', function (e) {
                if (!menu.contains(e.target) && menu.classList.contains('is-open')) { setMenu(false); }
            });
            menu.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && menu.classList.contains('is-open')) { e.stopPropagation(); setMenu(false, true); }
            });
        });
    }

    if (doc.readyState !== 'loading') { init(); } else { doc.addEventListener('DOMContentLoaded', init); }
})();
