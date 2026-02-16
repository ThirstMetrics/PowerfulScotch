<?php
/**
 * Homepage: Hero + Featured Map
 *
 * @package PowerfulScotch
 */

get_header(); ?>

<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Discover the World's <span class="text-accent">Distilleries</span></h1>
        <p class="hero-subtitle">An interactive map of distilleries across the globe &mdash; from the misty highlands of Scotland to the agave fields of Jalisco.</p>
        <div class="hero-actions">
            <a href="<?php echo esc_url(home_url('/map/?spirit=scotch')); ?>" class="btn btn-primary btn-lg">Explore Scotch Map</a>
            <a href="<?php echo esc_url(home_url('/map/')); ?>" class="btn btn-secondary btn-lg">Browse All Spirits</a>
        </div>
    </div>
</section>

<section class="spirit-cards">
    <div class="content-wrap">
        <h2 class="section-title">Choose Your Spirit</h2>
        <div class="cards-grid">
            <a href="<?php echo esc_url(home_url('/map/?spirit=scotch')); ?>" class="spirit-card spirit-card--scotch">
                <div class="spirit-card__icon">&#127867;</div>
                <h3 class="spirit-card__title">Scotch Whisky</h3>
                <p class="spirit-card__count">
                    <?php
                    $count = wp_count_posts('distillery');
                    echo esc_html($count->publish ?: '135');
                    ?> Distilleries
                </p>
                <p class="spirit-card__desc">From Speyside to Islay, explore every malt and grain distillery in Scotland.</p>
                <span class="spirit-card__cta">Explore Map &rarr;</span>
            </a>

            <div class="spirit-card spirit-card--tequila spirit-card--soon">
                <div class="spirit-card__icon">&#127818;</div>
                <h3 class="spirit-card__title">Tequila</h3>
                <p class="spirit-card__desc">The agave distilleries of Jalisco and beyond.</p>
                <span class="spirit-card__badge">Coming Soon</span>
            </div>

            <div class="spirit-card spirit-card--rum spirit-card--soon">
                <div class="spirit-card__icon">&#127860;</div>
                <h3 class="spirit-card__title">Rum</h3>
                <p class="spirit-card__desc">Caribbean and global rum distilleries.</p>
                <span class="spirit-card__badge">Coming Soon</span>
            </div>

            <div class="spirit-card spirit-card--sake spirit-card--soon">
                <div class="spirit-card__icon">&#127862;</div>
                <h3 class="spirit-card__title">Sake</h3>
                <p class="spirit-card__desc">Japan's finest sake breweries and distilleries.</p>
                <span class="spirit-card__badge">Coming Soon</span>
            </div>
        </div>
    </div>
</section>

<section class="map-preview">
    <div class="content-wrap">
        <h2 class="section-title">Scotland at a Glance</h2>
        <p class="section-desc">135 distilleries mapped across Scotland's whisky regions. Click any pin to learn more.</p>
        <div class="map-preview__container" id="homepage-map" data-spirit="scotch"></div>
        <div class="map-preview__cta">
            <a href="<?php echo esc_url(home_url('/map/?spirit=scotch')); ?>" class="btn btn-primary">Open Full Map</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('homepage-map');
    if (!mapEl || typeof L === 'undefined') return;

    var map = L.map('homepage-map', {
        center: [56.8, -4.5],
        zoom: 6,
        zoomControl: false,
        attributionControl: false,
        dragging: true,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
    }).addTo(map);

    // Fetch and show markers
    fetch('<?php echo esc_url(rest_url('powerful-scotch/v1/distilleries?spirit_type=scotch')); ?>')
        .then(function(r) { return r.json(); })
        .then(function(geojson) {
            if (!geojson.features) return;
            var markers = L.markerClusterGroup({ maxClusterRadius: 40 });
            geojson.features.forEach(function(f) {
                var coords = f.geometry.coordinates;
                var p = f.properties;
                var color = p.status === 'Silent' ? '#a0845c' : p.status === 'Mothballed' ? '#6b7280' : '#d4a574';
                var marker = L.circleMarker([coords[1], coords[0]], {
                    radius: 6, fillColor: color, color: '#0f0f1a', weight: 2, fillOpacity: 0.9,
                });
                marker.bindTooltip(p.name, { className: 'ps-tooltip' });
                markers.addLayer(marker);
            });
            map.addLayer(markers);
        });
});
</script>

<?php get_footer();
