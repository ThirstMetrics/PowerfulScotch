/**
 * Powerful Scotch — Detail Page Map
 *
 * Small Leaflet map on the single distillery detail page.
 */
(function () {
    'use strict';

    function init() {
        var mapEl = document.getElementById('detail-map');
        if (!mapEl || typeof L === 'undefined' || typeof psDetailData === 'undefined') return;

        var lat = psDetailData.lat;
        var lng = psDetailData.lng;
        var name = psDetailData.name;

        if (!lat && !lng) {
            mapEl.style.display = 'none';
            return;
        }

        var map = L.map('detail-map', {
            center: [lat, lng],
            zoom: 12,
            zoomControl: true,
            scrollWheelZoom: false,
            dragging: true,
            attributionControl: false,
        });

        // Dark tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
        }).addTo(map);

        // Marker
        var marker = L.circleMarker([lat, lng], {
            radius: 10,
            fillColor: '#d4a574',
            color: '#0f0f1a',
            weight: 3,
            fillOpacity: 1,
        }).addTo(map);

        marker.bindTooltip(name, {
            permanent: true,
            direction: 'top',
            offset: [0, -12],
            className: 'ps-tooltip',
        }).openTooltip();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
