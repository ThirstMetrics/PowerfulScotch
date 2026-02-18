<?php
/**
 * Taxonomies: Spirit Type & Region
 *
 * @package PowerfulSpirits
 */

defined('ABSPATH') || exit;

function ps_register_taxonomies() {
    // Spirit Type taxonomy
    register_taxonomy('spirit_type', 'distillery', [
        'labels' => [
            'name'              => 'Spirit Types',
            'singular_name'     => 'Spirit Type',
            'search_items'      => 'Search Spirit Types',
            'all_items'         => 'All Spirit Types',
            'edit_item'         => 'Edit Spirit Type',
            'update_item'       => 'Update Spirit Type',
            'add_new_item'      => 'Add New Spirit Type',
            'new_item_name'     => 'New Spirit Type Name',
            'menu_name'         => 'Spirit Types',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'spirit', 'with_front' => false],
    ]);

    // Region taxonomy
    register_taxonomy('region', 'distillery', [
        'labels' => [
            'name'              => 'Regions',
            'singular_name'     => 'Region',
            'search_items'      => 'Search Regions',
            'all_items'         => 'All Regions',
            'parent_item'       => 'Parent Region',
            'parent_item_colon' => 'Parent Region:',
            'edit_item'         => 'Edit Region',
            'update_item'       => 'Update Region',
            'add_new_item'      => 'Add New Region',
            'new_item_name'     => 'New Region Name',
            'menu_name'         => 'Regions',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'region', 'with_front' => false],
    ]);
}
add_action('init', 'ps_register_taxonomies');

/**
 * Pre-populate spirit type terms on theme activation
 */
function ps_create_default_terms() {
    $spirit_types = ['Scotch', 'Tequila', 'Rum', 'Sake'];
    foreach ($spirit_types as $type) {
        if (!term_exists($type, 'spirit_type')) {
            wp_insert_term($type, 'spirit_type', [
                'slug' => sanitize_title($type),
            ]);
        }
    }

    // Scotch regions
    $scotch_term = term_exists('Scotch', 'spirit_type');
    $scotch_regions = [
        'Speyside', 'Islay', 'Highlands', 'North Highlands', 'West Highlands',
        'Eastern Highlands', 'Lowlands', 'Campbeltown', 'Islands', 'Midlands',
    ];
    foreach ($scotch_regions as $region) {
        if (!term_exists($region, 'region')) {
            wp_insert_term($region, 'region');
        }
    }

    // Rum geographic regions
    $rum_regions = [
        'Caribbean', 'Central America', 'South America', 'North America',
        'Europe', 'Africa', 'Asia-Pacific',
    ];
    foreach ($rum_regions as $region) {
        if (!term_exists($region, 'region')) {
            wp_insert_term($region, 'region');
        }
    }

    // Tequila regions
    $tequila_regions = [
        'Los Altos (Highlands)', 'Tequila Valley (Lowlands)',
        'Central Jalisco', 'Guanajuato', 'Tamaulipas',
    ];
    foreach ($tequila_regions as $region) {
        if (!term_exists($region, 'region')) {
            wp_insert_term($region, 'region');
        }
    }
}
add_action('after_switch_theme', 'ps_create_default_terms');
