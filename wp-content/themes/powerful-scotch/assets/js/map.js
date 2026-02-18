/**
 * Powerful Spirits — Main Map Controller
 *
 * Leaflet.js map with clustering, minimap, tooltips, filtering,
 * search, deep-linking, and mobile support.
 */
(function () {
    'use strict';

    // Spirit default views
    var SPIRIT_VIEWS = {
        scotch:  { center: [56.8, -4.5],    zoom: 7  },
        tequila: { center: [20.7, -103.3],  zoom: 7  },
        rum:     { center: [15.0, -40.0],   zoom: 3  },
        sake:    { center: [36.2, 138.2],   zoom: 6  },
    };

    var AVAILABLE_SPIRITS = ['scotch', 'rum', 'tequila', 'sake']; // Spirits with actual data

    // State
    var map, clusterGroup, miniMap, allFeatures = [], filteredFeatures = [];
    var currentSpirit = 'scotch';
    var isMobile = window.innerWidth < 768;
    var markerLookup = {}; // slug -> { marker, feature }

    // DOM elements
    var mapEl, sidebar, sidebarContent, sidebarClose;
    var bottomsheet, bottomsheetContent;
    var comingSoon, searchInput, searchResults;
    var filterToggle, filterDropdown, countEl;

    /**
     * Initialize
     */
    function init() {
        mapEl            = document.getElementById('distillery-map');
        sidebar          = document.getElementById('map-sidebar');
        sidebarContent   = document.getElementById('sidebar-content');
        sidebarClose     = document.getElementById('sidebar-close');
        bottomsheet      = document.getElementById('map-bottomsheet');
        bottomsheetContent = document.getElementById('bottomsheet-content');
        comingSoon        = document.getElementById('map-coming-soon');
        searchInput       = document.getElementById('map-search');
        searchResults     = document.getElementById('search-results');
        filterToggle      = document.getElementById('filter-toggle');
        filterDropdown    = document.getElementById('filter-dropdown');
        countEl           = document.getElementById('distillery-count');

        if (!mapEl || typeof L === 'undefined') return;

        // Parse URL params
        var params = new URLSearchParams(window.location.search);
        currentSpirit = params.get('spirit') || 'scotch';

        // Set active tab in nav
        setActiveTab(currentSpirit);

        // Check if spirit has data
        if (AVAILABLE_SPIRITS.indexOf(currentSpirit) === -1) {
            showComingSoon();
            initMap(SPIRIT_VIEWS[currentSpirit] || SPIRIT_VIEWS.scotch);
            return;
        }

        hideComingSoon();
        initMap(SPIRIT_VIEWS[currentSpirit] || SPIRIT_VIEWS.scotch);
        loadDistilleries(currentSpirit);

        // Events
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        if (searchInput) searchInput.addEventListener('input', onSearchInput);
        if (filterToggle) filterToggle.addEventListener('click', toggleFilter);

        document.addEventListener('click', function (e) {
            if (filterDropdown && !filterDropdown.contains(e.target) && e.target !== filterToggle) {
                filterDropdown.hidden = true;
                if (filterToggle) filterToggle.setAttribute('aria-expanded', 'false');
            }
            if (searchResults && !searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.hidden = true;
            }
        });

        // Responsive
        window.addEventListener('resize', function () {
            isMobile = window.innerWidth < 768;
        });
    }

    /**
     * Create the Leaflet map
     */
    function initMap(view) {
        map = L.map('distillery-map', {
            center: view.center,
            zoom: view.zoom,
            zoomControl: true,
            preferCanvas: true,
        });

        // Dark CartoDB tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19,
        }).addTo(map);

        // Minimap with lighter tiles for contrast
        var miniMapLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
        });
        miniMap = new L.Control.MiniMap(miniMapLayer, {
            toggleDisplay: true,
            minimized: isMobile,
            position: 'bottomright',
            width: 150,
            height: 120,
            zoomLevelOffset: -5,
        }).addTo(map);

        // Cluster group
        clusterGroup = L.markerClusterGroup({
            maxClusterRadius: 45,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            iconCreateFunction: function (cluster) {
                var count = cluster.getChildCount();
                var size = count < 10 ? 'small' : count < 30 ? 'medium' : 'large';
                return L.divIcon({
                    html: '<div>' + count + '</div>',
                    className: 'marker-cluster marker-cluster-' + size,
                    iconSize: L.point(40, 40),
                });
            },
        });

        map.addLayer(clusterGroup);
    }

    /**
     * Fetch distillery data from REST API
     */
    function loadDistilleries(spirit) {
        var url = psMapData.restUrl + '?spirit_type=' + encodeURIComponent(spirit);

        fetch(url, {
            headers: { 'X-WP-Nonce': psMapData.nonce },
        })
        .then(function (res) { return res.json(); })
        .then(function (geojson) {
            if (!geojson || !geojson.features) return;
            allFeatures = geojson.features;
            filteredFeatures = allFeatures;
            renderMarkers(allFeatures);
            buildRegionFilters(allFeatures);
            updateCount(allFeatures.length);

            // Auto-fit bounds for non-scotch spirits (global coverage)
            if (spirit !== 'scotch' && allFeatures.length > 0) {
                var bounds = [];
                allFeatures.forEach(function (f) {
                    bounds.push([f.geometry.coordinates[1], f.geometry.coordinates[0]]);
                });
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 10 });
            }

            checkDeepLink();
        })
        .catch(function (err) {
            console.error('Failed to load distilleries:', err);
        });
    }

    /**
     * Render markers on map
     */
    function renderMarkers(features) {
        clusterGroup.clearLayers();
        markerLookup = {};

        features.forEach(function (f) {
            var coords = f.geometry.coordinates;
            var p = f.properties;
            var lat = coords[1];
            var lng = coords[0];

            var color = getStatusColor(p.status);
            var isGrain = p.type === 'Grain';

            // Create marker
            var marker;
            if (isGrain) {
                marker = L.marker([lat, lng], {
                    icon: createMarkerIcon(p.status, true),
                });
            } else {
                marker = L.circleMarker([lat, lng], {
                    radius: 7,
                    fillColor: color,
                    color: '#0f0f1a',
                    weight: 2,
                    fillOpacity: 0.9,
                });
            }

            // Tooltip (hover)
            var locationParts = [];
            if (p.country) locationParts.push(escHtml(p.country));
            if (p.region) locationParts.push(escHtml(p.region));
            var locationStr = locationParts.join(', ');

            var spiritLabel = getSpiritLabel(p);

            var tooltipHtml = '<div class="popup-name">' + escHtml(p.name) + '</div>' +
                (p.nom_number ? '<div class="popup-nom">NOM ' + escHtml(p.nom_number) + '</div>' : '') +
                '<div class="popup-meta">' +
                    '<span class="status-badge status-badge--' + escHtml(p.status.toLowerCase()) + ' status-badge--sm">' + escHtml(p.status) + '</span>' +
                    (locationStr ? '<span class="meta-tag meta-tag--sm">' + locationStr + '</span>' : '') +
                    (spiritLabel ? '<span class="meta-tag meta-tag--sm">' + escHtml(spiritLabel) + '</span>' : '') +
                '</div>' +
                '<div class="popup-facts">' +
                    (p.type ? '<div>' + escHtml(p.type) + (p.year ? ' &middot; ' + escHtml(p.year) : '') + '</div>' : (p.year ? '<div>' + escHtml(p.year) + '</div>' : '')) +
                    (p.raw_material ? '<div>Raw material: ' + escHtml(p.raw_material) + '</div>' : '') +
                '</div>' +
                (p.official_website ? '<a class="popup-link" href="' + escHtml(p.official_website) + '" target="_blank" rel="noopener noreferrer">Visit &rarr;</a>' : '');

            // Hover popup (stays open so user can click Visit link)
            marker.bindPopup(tooltipHtml, {
                className: 'ps-popup',
                closeButton: false,
                offset: [0, -8],
                autoPan: false,
            });

            marker.on('mouseover', function () {
                this.openPopup();
            });

            // Click handler — close popup, open sidebar
            marker.on('click', function () {
                this.closePopup();
                onMarkerClick(p, lat, lng);
            });

            markerLookup[p.slug] = { marker: marker, feature: f };
            clusterGroup.addLayer(marker);
        });
    }

    /**
     * Handle marker click
     */
    function onMarkerClick(p, lat, lng) {
        // Update URL without navigation
        var url = new URL(window.location);
        url.searchParams.set('distillery', p.slug);
        window.history.replaceState(null, '', url.toString());

        // Center map
        map.setView([lat, lng], Math.max(map.getZoom(), 10), { animate: true });

        var html = buildDetailPanel(p);

        if (isMobile) {
            showBottomsheet(html);
        } else {
            showSidebar(html);
        }
    }

    /**
     * Build detail panel HTML
     */
    function buildDetailPanel(p) {
        var spiritLabel = getSpiritLabel(p);
        var html = '<h3 class="sidebar-distillery-name">' + escHtml(p.name) + '</h3>';
        html += '<div class="sidebar-meta">';
        html += '<span class="status-badge status-badge--' + escHtml(p.status.toLowerCase()) + '">' + escHtml(p.status) + '</span>';
        if (p.country) html += '<span class="meta-tag">' + escHtml(p.country) + '</span>';
        if (p.region) html += '<span class="meta-tag">' + escHtml(p.region) + '</span>';
        if (spiritLabel) html += '<span class="meta-tag">' + escHtml(spiritLabel) + '</span>';
        if (p.type) html += '<span class="meta-tag">' + escHtml(p.type) + '</span>';
        html += '</div>';

        html += '<div class="sidebar-facts">';
        if (p.nom_number) html += buildFact('NOM', p.nom_number);
        if (p.name_japanese) html += buildFact('Japanese Name', p.name_japanese);
        if (p.country) html += buildFact('Country', p.country);
        if (p.prefecture) html += buildFact('Prefecture', p.prefecture);
        if (p.year) html += buildFact('Years Active', p.year);
        if (p.region) html += buildFact('Region', p.region);
        if (p.type) html += buildFact('Type', p.type);
        html += buildFact('Status', p.status);
        if (p.owner) html += buildFact('Owner', p.owner);
        if (p.cooking_method) html += buildFact('Cooking Method', p.cooking_method);
        if (p.raw_material) html += buildFact('Raw Material', p.raw_material);
        if (p.rice_varieties) html += buildFact('Rice Varieties', p.rice_varieties);
        if (p.toji_school) html += buildFact('Toji School', p.toji_school);
        if (p.still_types) html += buildFact('Still Types', p.still_types);
        if (p.barrel_sources) html += buildFact('Barrel Sources', p.barrel_sources);
        if (p.production_capacity) html += buildFact('Production Capacity', p.production_capacity);
        if (p.production_size) html += buildFact('Production Size', p.production_size);
        if (p.key_brands) html += buildFact('Key Brands', p.key_brands);
        if (p.expressions) html += buildFact('Expressions', p.expressions);
        if (p.official_website) {
            var displayUrl = p.official_website.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '');
            html += '<div class="sidebar-fact"><span class="sidebar-fact__label">Website</span><span class="sidebar-fact__value"><a href="' + escHtml(p.official_website) + '" target="_blank" rel="noopener noreferrer">' + escHtml(displayUrl) + '</a></span></div>';
        }
        html += '</div>';

        html += '<a href="' + escHtml(p.url) + '" class="btn btn-primary" style="width:100%;justify-content:center;">View Full Details</a>';

        return html;
    }

    function buildFact(label, value) {
        return '<div class="sidebar-fact"><span class="sidebar-fact__label">' + escHtml(label) + '</span><span class="sidebar-fact__value">' + escHtml(value) + '</span></div>';
    }

    /**
     * Sidebar
     */
    function showSidebar(html) {
        if (!sidebar || !sidebarContent) return;
        sidebarContent.innerHTML = html;
        sidebar.classList.add('open');
        map.invalidateSize({ animate: true });
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        map.invalidateSize({ animate: true });

        var url = new URL(window.location);
        url.searchParams.delete('distillery');
        window.history.replaceState(null, '', url.toString());
    }

    /**
     * Bottom sheet (mobile)
     */
    function showBottomsheet(html) {
        if (!bottomsheet || !bottomsheetContent) return;
        bottomsheetContent.innerHTML = html;
        bottomsheet.hidden = false;
        requestAnimationFrame(function () {
            bottomsheet.classList.add('open');
        });
    }

    function hideBottomsheet() {
        if (!bottomsheet) return;
        bottomsheet.classList.remove('open');
        setTimeout(function () { bottomsheet.hidden = true; }, 300);
    }

    /**
     * Coming Soon
     */
    function showComingSoon() {
        if (!comingSoon) return;
        comingSoon.innerHTML =
            '<div class="coming-soon-content">' +
                '<div class="coming-soon-icon">&#127758;</div>' +
                '<h2>Coming Soon</h2>' +
                '<p>We\'re tracking down distilleries for this spirit category. Check back soon!</p>' +
                '<a href="' + psMapData.siteUrl + '/map/?spirit=scotch" class="btn btn-primary">View Scotch Map</a>' +
            '</div>';
        comingSoon.classList.add('visible');
    }
    function hideComingSoon() {
        if (comingSoon) comingSoon.classList.remove('visible');
    }

    /**
     * Region Filters
     */
    function buildRegionFilters(features) {
        if (!filterDropdown) return;

        var regions = {};
        features.forEach(function (f) {
            var r = f.properties.region;
            if (r) {
                // Normalize sub-regions: "Speyside (Livet)" -> "Speyside"
                var base = r.replace(/\s*\(.*\)/, '');
                regions[base] = (regions[base] || 0) + 1;
            }
        });

        var html = '<button class="filter-chip active" data-region="all">All (' + features.length + ')</button>';
        var sorted = Object.keys(regions).sort();
        sorted.forEach(function (r) {
            html += '<button class="filter-chip" data-region="' + escHtml(r) + '">' + escHtml(r) + ' <span class="filter-chip__count">(' + regions[r] + ')</span></button>';
        });

        filterDropdown.innerHTML = html;

        // Bind filter clicks
        filterDropdown.querySelectorAll('.filter-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                filterDropdown.querySelectorAll('.filter-chip').forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');

                var region = chip.getAttribute('data-region');
                filterByRegion(region);
            });
        });
    }

    function filterByRegion(region) {
        if (region === 'all') {
            filteredFeatures = allFeatures;
        } else {
            filteredFeatures = allFeatures.filter(function (f) {
                var r = f.properties.region || '';
                return r.replace(/\s*\(.*\)/, '') === region;
            });
        }

        renderMarkers(filteredFeatures);
        updateCount(filteredFeatures.length);

        // Fit bounds to filtered markers if not "all"
        if (region !== 'all' && filteredFeatures.length > 0) {
            var bounds = [];
            filteredFeatures.forEach(function (f) {
                bounds.push([f.geometry.coordinates[1], f.geometry.coordinates[0]]);
            });
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 10 });
        }
    }

    function toggleFilter() {
        if (!filterDropdown || !filterToggle) return;
        var open = filterDropdown.hidden;
        filterDropdown.hidden = !open;
        filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    /**
     * Search
     */
    function onSearchInput() {
        var query = searchInput.value.trim().toLowerCase();
        if (query.length < 2) {
            searchResults.hidden = true;
            return;
        }

        var matches = allFeatures.filter(function (f) {
            return f.properties.name.toLowerCase().indexOf(query) !== -1;
        }).slice(0, 10);

        if (matches.length === 0) {
            searchResults.innerHTML = '<div class="search-result-item"><span class="search-result-item__name">No results</span></div>';
            searchResults.hidden = false;
            return;
        }

        var html = '';
        matches.forEach(function (f) {
            var p = f.properties;
            html += '<div class="search-result-item" data-slug="' + escHtml(p.slug) + '">';
            html += '<div class="search-result-item__name">' + escHtml(p.name) + '</div>';
            html += '<div class="search-result-item__meta">' + escHtml(p.region) + ' &middot; ' + escHtml(p.status) + '</div>';
            html += '</div>';
        });

        searchResults.innerHTML = html;
        searchResults.hidden = false;

        // Bind clicks
        searchResults.querySelectorAll('.search-result-item').forEach(function (item) {
            item.addEventListener('click', function () {
                var slug = item.getAttribute('data-slug');
                focusDistillery(slug);
                searchResults.hidden = true;
                searchInput.value = '';
            });
        });
    }

    /**
     * Focus on a distillery by slug
     */
    function focusDistillery(slug) {
        var entry = markerLookup[slug];
        if (!entry) return;

        var f = entry.feature;
        var p = f.properties;
        var lat = f.geometry.coordinates[1];
        var lng = f.geometry.coordinates[0];

        // Zoom to marker, spiderfy if in cluster
        clusterGroup.zoomToShowLayer(entry.marker, function () {
            map.setView([lat, lng], Math.max(map.getZoom(), 12), { animate: true });
            entry.marker.openPopup();
            onMarkerClick(p, lat, lng);
        });
    }

    /**
     * Deep-link: check URL for ?distillery=slug
     */
    function checkDeepLink() {
        var params = new URLSearchParams(window.location.search);
        var slug = params.get('distillery');
        if (slug) {
            // Small delay to let markers settle
            setTimeout(function () {
                focusDistillery(slug);
            }, 500);
        }
    }

    /**
     * Update active spirit tab in header
     */
    function setActiveTab(spirit) {
        document.querySelectorAll('.spirit-tab, .mobile-spirit-tab').forEach(function (tab) {
            tab.classList.toggle('active', tab.getAttribute('data-spirit') === spirit);
        });
    }

    /**
     * Update distillery count display
     */
    function updateCount(n) {
        if (countEl) {
            countEl.textContent = n + ' distiller' + (n === 1 ? 'y' : 'ies');
        }
    }

    /**
     * Get color for distillery status
     */
    function getStatusColor(status) {
        switch ((status || '').toLowerCase()) {
            case 'operating':  return '#4ade80';
            case 'silent':     return '#a0845c';
            case 'mothballed': return '#6b7280';
            default:           return '#d4a574';
        }
    }

    /**
     * Create a custom icon for grain distilleries (square marker)
     */
    function createMarkerIcon(status, isGrain) {
        var color = getStatusColor(status);
        var svg = isGrain
            ? '<svg width="16" height="16" viewBox="0 0 16 16"><rect x="2" y="2" width="12" height="12" rx="2" fill="' + color + '" stroke="#0f0f1a" stroke-width="1.5"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 16 16"><circle cx="8" cy="8" r="6" fill="' + color + '" stroke="#0f0f1a" stroke-width="1.5"/></svg>';

        return L.divIcon({
            html: svg,
            className: 'ps-marker-icon',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });
    }

    /**
     * French-tradition rum countries → "Rhum", all others → "Rum"
     */
    var RHUM_COUNTRIES = [
        'Martinique', 'Guadeloupe', 'Haiti', 'Réunion', 'Reunion', 'Madagascar',
    ];

    function getSpiritLabel(p) {
        var st = (p.spirit_type || '').toLowerCase();
        if (st !== 'rum') return p.spirit_type || '';
        if (p.country && RHUM_COUNTRIES.indexOf(p.country) !== -1) return 'Rhum';
        return 'Rum';
    }

    /**
     * Escape HTML
     */
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Boot
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
