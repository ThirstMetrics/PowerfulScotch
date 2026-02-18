<?php
/**
 * Powerful Spirits Theme Functions
 *
 * @package PowerfulSpirits
 */

defined('ABSPATH') || exit;

define('PS_VERSION', '1.3.0');
define('PS_DIR', get_template_directory());
define('PS_URI', get_template_directory_uri());

// Include modules
require_once PS_DIR . '/inc/cpt-distillery.php';
require_once PS_DIR . '/inc/taxonomies.php';
require_once PS_DIR . '/inc/rest-api.php';
require_once PS_DIR . '/inc/acf-fields.php';

/**
 * Theme setup
 */
function ps_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ]);

    register_nav_menus([
        'primary' => __('Primary Navigation', 'powerful-spirits'),
    ]);
}
add_action('after_setup_theme', 'ps_setup');

/**
 * Enqueue styles and scripts
 */
function ps_enqueue_assets() {
    // Leaflet CSS
    wp_enqueue_style(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        [],
        '1.9.4'
    );

    // Leaflet MarkerCluster CSS
    wp_enqueue_style(
        'leaflet-markercluster',
        'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
        ['leaflet'],
        '1.5.3'
    );
    wp_enqueue_style(
        'leaflet-markercluster-default',
        'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
        ['leaflet-markercluster'],
        '1.5.3'
    );

    // Leaflet MiniMap CSS
    wp_enqueue_style(
        'leaflet-minimap',
        'https://unpkg.com/leaflet-minimap@3.6.1/dist/Control.MiniMap.min.css',
        ['leaflet'],
        '3.6.1'
    );

    // Theme styles
    wp_enqueue_style(
        'powerful-spirits-main',
        PS_URI . '/assets/css/main.css',
        ['leaflet', 'leaflet-markercluster', 'leaflet-minimap'],
        PS_VERSION
    );

    // Leaflet JS
    wp_enqueue_script(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        '1.9.4',
        true
    );

    // Leaflet MarkerCluster JS
    wp_enqueue_script(
        'leaflet-markercluster',
        'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',
        ['leaflet'],
        '1.5.3',
        true
    );

    // Leaflet MiniMap JS
    wp_enqueue_script(
        'leaflet-minimap',
        'https://unpkg.com/leaflet-minimap@3.6.1/dist/Control.MiniMap.min.js',
        ['leaflet'],
        '3.6.1',
        true
    );

    // Spirit switcher (loaded on all pages for nav)
    wp_enqueue_script(
        'ps-spirit-switcher',
        PS_URI . '/assets/js/spirit-switcher.js',
        [],
        PS_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'ps_enqueue_assets');

/**
 * Enqueue map page scripts
 */
function ps_enqueue_map_assets() {
    if (is_page_template('page-map.php')) {
        wp_enqueue_script(
            'ps-map',
            PS_URI . '/assets/js/map.js',
            ['leaflet', 'leaflet-markercluster', 'leaflet-minimap'],
            PS_VERSION,
            true
        );

        wp_localize_script('ps-map', 'psMapData', [
            'restUrl'    => rest_url('powerful-spirits/v1/distilleries'),
            'nonce'      => wp_create_nonce('wp_rest'),
            'themeUrl'   => PS_URI,
            'siteUrl'    => home_url(),
            'mapPageUrl' => get_permalink(get_page_by_path('map')),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'ps_enqueue_map_assets');

/**
 * Enqueue detail page map script
 */
function ps_enqueue_detail_map() {
    if (is_singular('distillery')) {
        wp_enqueue_script(
            'ps-detail-map',
            PS_URI . '/assets/js/detail-map.js',
            ['leaflet'],
            PS_VERSION,
            true
        );

        $post_id = get_the_ID();
        wp_localize_script('ps-detail-map', 'psDetailData', [
            'lat'      => (float) get_post_meta($post_id, 'latitude', true),
            'lng'      => (float) get_post_meta($post_id, 'longitude', true),
            'name'     => get_the_title(),
            'themeUrl' => PS_URI,
        ]);
    }
}
add_action('wp_enqueue_scripts', 'ps_enqueue_detail_map');

/**
 * Flush rewrite rules on theme activation
 */
function ps_activate() {
    ps_register_distillery_cpt();
    ps_register_taxonomies();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'ps_activate');

/**
 * Add body classes
 */
function ps_body_classes($classes) {
    if (is_page_template('page-map.php')) {
        $classes[] = 'page-map';
    }
    if (is_singular('distillery')) {
        $classes[] = 'single-distillery-page';
    }
    return $classes;
}
add_filter('body_class', 'ps_body_classes');

/**
 * Invalidate REST transient cache on distillery save
 */
function ps_invalidate_cache($post_id, $post) {
    if ($post->post_type !== 'distillery') {
        return;
    }
    delete_transient('ps_distilleries_scotch');
    delete_transient('ps_distilleries_tequila');
    delete_transient('ps_distilleries_rum');
    delete_transient('ps_distilleries_sake');
    delete_transient('ps_distilleries_all');
}
add_action('save_post', 'ps_invalidate_cache', 10, 2);
