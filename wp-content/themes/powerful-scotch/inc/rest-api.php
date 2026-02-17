<?php
/**
 * Custom REST API Endpoint for Distillery GeoJSON
 *
 * @package PowerfulSpirits
 */

defined('ABSPATH') || exit;

function ps_register_rest_routes() {
    register_rest_route('powerful-spirits/v1', '/distilleries', [
        'methods'             => 'GET',
        'callback'            => 'ps_get_distilleries_geojson',
        'permission_callback' => '__return_true',
        'args'                => [
            'spirit_type' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
            'region' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
        ],
    ]);
}
add_action('rest_api_init', 'ps_register_rest_routes');

/**
 * Return distilleries as GeoJSON FeatureCollection
 */
function ps_get_distilleries_geojson(WP_REST_Request $request) {
    $spirit_type = $request->get_param('spirit_type');
    $region      = $request->get_param('region');

    // Build cache key
    $cache_key = 'ps_distilleries_' . ($spirit_type ?: 'all');
    if ($region) {
        $cache_key .= '_' . sanitize_key($region);
    }

    // Check transient cache
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return new WP_REST_Response($cached, 200);
    }

    // Query distilleries
    $args = [
        'post_type'      => 'distillery',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];

    $tax_query = [];

    if ($spirit_type) {
        $tax_query[] = [
            'taxonomy' => 'spirit_type',
            'field'    => 'slug',
            'terms'    => $spirit_type,
        ];
    }

    if ($region) {
        $tax_query[] = [
            'taxonomy' => 'region',
            'field'    => 'slug',
            'terms'    => $region,
        ];
    }

    if (!empty($tax_query)) {
        $tax_query['relation'] = 'AND';
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);
    $features = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $lat = (float) get_post_meta($post_id, 'latitude', true);
            $lng = (float) get_post_meta($post_id, 'longitude', true);

            // Skip posts without coordinates
            if (!$lat && !$lng) {
                continue;
            }

            $regions = wp_get_post_terms($post_id, 'region', ['fields' => 'names']);
            $region_name = !empty($regions) ? $regions[0] : '';

            $spirit_types = wp_get_post_terms($post_id, 'spirit_type', ['fields' => 'names']);
            $spirit_name = !empty($spirit_types) ? $spirit_types[0] : '';

            $status = get_post_meta($post_id, 'distillery_status', true);
            $type   = get_post_meta($post_id, 'distillery_type', true);
            $year   = get_post_meta($post_id, 'year_founded', true);
            $year_closed = get_post_meta($post_id, 'year_closed', true);
            $website = get_post_meta($post_id, 'official_website', true);
            $owner   = get_post_meta($post_id, 'owner', true);
            $still_types    = get_post_meta($post_id, 'still_types', true);
            $expressions    = get_post_meta($post_id, 'expressions', true);
            $barrel_sources = get_post_meta($post_id, 'barrel_sources', true);
            $raw_material   = get_post_meta($post_id, 'raw_material', true);
            $country        = get_post_meta($post_id, 'country', true);

            $year_display = $year;
            if ($year_closed) {
                $year_display .= ' - ' . $year_closed;
            } elseif ($year) {
                $year_display .= ' - present';
            }

            $features[] = [
                'type'     => 'Feature',
                'geometry' => [
                    'type'        => 'Point',
                    'coordinates' => [$lng, $lat], // GeoJSON is [lng, lat]
                ],
                'properties' => [
                    'id'               => $post_id,
                    'name'             => get_the_title(),
                    'slug'             => get_post_field('post_name', $post_id),
                    'region'           => $region_name,
                    'spirit_type'      => $spirit_name,
                    'type'             => $type ?: '',
                    'status'           => $status ?: 'Operating',
                    'year'             => $year_display,
                    'url'              => get_permalink($post_id),
                    'official_website' => $website,
                    'owner'            => $owner,
                    'still_types'      => $still_types,
                    'expressions'      => $expressions,
                    'barrel_sources'   => $barrel_sources,
                    'raw_material'     => $raw_material,
                    'country'          => $country,
                ],
            ];
        }
        wp_reset_postdata();
    }

    $geojson = [
        'type'     => 'FeatureCollection',
        'features' => $features,
    ];

    // Cache for 1 hour
    set_transient($cache_key, $geojson, HOUR_IN_SECONDS);

    return new WP_REST_Response($geojson, 200);
}
