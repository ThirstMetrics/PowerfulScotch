/**
 * Powerful Spirits — Spirit Switcher & Mobile Menu
 *
 * Handles spirit tab navigation and mobile menu toggle.
 */
(function () {
    'use strict';

    function init() {
        // Mobile menu toggle
        var toggle = document.getElementById('mobile-menu-toggle');
        var mobileNav = document.getElementById('mobile-nav');

        if (toggle && mobileNav) {
            toggle.addEventListener('click', function () {
                toggle.classList.toggle('active');
                mobileNav.classList.toggle('open');
            });
        }

        // Spirit tab click handling (for map page without full reload)
        var isMapPage = document.body.classList.contains('page-map');
        if (!isMapPage) return;

        // On the map page, spirit tabs update URL and reload map data
        document.querySelectorAll('.spirit-tab, .mobile-spirit-tab').forEach(function (tab) {
            var spirit = tab.getAttribute('data-spirit');
            if (!spirit) return;

            tab.addEventListener('click', function (e) {
                // Let the normal link behavior handle navigation
                // The map page reads ?spirit= on load
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
