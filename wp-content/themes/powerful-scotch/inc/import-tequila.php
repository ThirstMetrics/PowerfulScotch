<?php
/**
 * One-Time Data Import: Tequila Distilleries → WordPress
 *
 * Usage (WP-CLI):
 *   wp eval-file wp-content/themes/powerful-scotch/inc/import-tequila.php
 *
 * @package PowerfulSpirits
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/tequila-data.php';

/**
 * Main tequila import function
 */
function ps_run_tequila_import() {
    // Ensure taxonomies & CPT are registered
    if (!post_type_exists('distillery')) {
        ps_register_distillery_cpt();
    }
    if (!taxonomy_exists('spirit_type')) {
        ps_register_taxonomies();
    }

    // Create default terms including tequila regions
    ps_create_default_terms();

    $distilleries = ps_get_tequila_distillery_data();
    $imported = 0;
    $skipped  = 0;

    foreach ($distilleries as $d) {
        // Check if already exists by title
        $existing = get_posts([
            'post_type'      => 'distillery',
            'title'          => $d['name'],
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ]);
        if (!empty($existing)) {
            $skipped++;
            if (defined('WP_CLI')) {
                WP_CLI::log("Skipped (exists): {$d['name']}");
            }
            continue;
        }

        // Create post with production summary as body content
        $post_id = wp_insert_post([
            'post_title'   => $d['name'],
            'post_content' => $d['post_content'],
            'post_type'    => 'distillery',
            'post_status'  => 'publish',
            'post_name'    => sanitize_title($d['name']),
        ]);

        if (is_wp_error($post_id)) {
            if (defined('WP_CLI')) {
                WP_CLI::warning('Failed to create: ' . $d['name']);
            }
            continue;
        }

        // Set meta fields
        update_post_meta($post_id, 'latitude', $d['lat']);
        update_post_meta($post_id, 'longitude', $d['lng']);
        update_post_meta($post_id, 'distillery_status', 'Operating');
        update_post_meta($post_id, 'country', 'Mexico');
        update_post_meta($post_id, 'official_website', $d['website']);
        update_post_meta($post_id, 'water_source', $d['water_source']);
        update_post_meta($post_id, 'still_count', $d['still_count']);
        update_post_meta($post_id, 'still_types', $d['still_types']);
        update_post_meta($post_id, 'barrel_sources', $d['barrel_sources']);
        update_post_meta($post_id, 'raw_material', $d['raw_material']);

        // Tequila-specific fields
        update_post_meta($post_id, 'nom_number', $d['nom']);
        update_post_meta($post_id, 'cooking_method', $d['cooking_method']);
        update_post_meta($post_id, 'production_capacity', $d['production_capacity']);

        // Year founded
        if (!empty($d['year_founded']) && $d['year_founded'] !== 'Unknown') {
            // Extract just the year number if there's extra text
            $year = $d['year_founded'];
            if (preg_match('/^(\d{4})/', $year, $m)) {
                update_post_meta($post_id, 'year_founded', $m[1]);
            } else {
                update_post_meta($post_id, 'year_founded', $year);
            }
        }

        // Set spirit type taxonomy
        wp_set_object_terms($post_id, 'Tequila', 'spirit_type');

        // Set region taxonomy
        if (!empty($d['region'])) {
            wp_set_object_terms($post_id, $d['region'], 'region');
        }

        $imported++;

        if (defined('WP_CLI')) {
            WP_CLI::log("Imported: {$d['name']} (NOM {$d['nom']}, {$d['region']}, {$d['lat']}, {$d['lng']})");
        }
    }

    // Flush transients
    delete_transient('ps_distilleries_tequila');
    delete_transient('ps_distilleries_all');

    if (defined('WP_CLI')) {
        WP_CLI::log("Done. Imported: $imported, Skipped: $skipped");
    }

    return ['imported' => $imported, 'skipped' => $skipped];
}

// Auto-run when eval-file'd via WP-CLI
if (defined('WP_CLI') && WP_CLI) {
    ps_run_tequila_import();
}
