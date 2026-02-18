<?php
/**
 * ACF Field Group Definitions for Distillery
 *
 * If ACF is active, registers field groups programmatically.
 * If ACF is not installed, falls back to native meta boxes.
 *
 * @package PowerfulSpirits
 */

defined('ABSPATH') || exit;

/**
 * Register ACF field groups if ACF is available
 */
function ps_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'      => 'group_distillery_details',
        'title'    => 'Distillery Details',
        'fields'   => [
            [
                'key'          => 'field_latitude',
                'label'        => 'Latitude',
                'name'         => 'latitude',
                'type'         => 'number',
                'instructions' => 'GPS latitude (e.g., 55.6314)',
                'required'     => 1,
                'step'         => 0.0001,
            ],
            [
                'key'          => 'field_longitude',
                'label'        => 'Longitude',
                'name'         => 'longitude',
                'type'         => 'number',
                'instructions' => 'GPS longitude (e.g., -6.1264)',
                'required'     => 1,
                'step'         => 0.0001,
            ],
            [
                'key'          => 'field_distillery_status',
                'label'        => 'Status',
                'name'         => 'distillery_status',
                'type'         => 'select',
                'choices'      => [
                    'Operating'  => 'Operating',
                    'Silent'     => 'Silent',
                    'Mothballed' => 'Mothballed',
                ],
                'default_value' => 'Operating',
            ],
            [
                'key'          => 'field_distillery_type',
                'label'        => 'Distillery Type',
                'name'         => 'distillery_type',
                'type'         => 'select',
                'choices'      => [
                    'Malt'         => 'Malt',
                    'Grain'        => 'Grain',
                    'Malt & Grain' => 'Malt & Grain',
                ],
                'default_value' => 'Malt',
            ],
            [
                'key'          => 'field_year_founded',
                'label'        => 'Year Founded',
                'name'         => 'year_founded',
                'type'         => 'text',
                'instructions' => 'e.g., 1815',
            ],
            [
                'key'          => 'field_year_closed',
                'label'        => 'Year Closed',
                'name'         => 'year_closed',
                'type'         => 'text',
                'instructions' => 'Leave blank if still operating',
            ],
            [
                'key'          => 'field_official_website',
                'label'        => 'Official Website',
                'name'         => 'official_website',
                'type'         => 'url',
            ],
            [
                'key'          => 'field_owner',
                'label'        => 'Owner',
                'name'         => 'owner',
                'type'         => 'text',
            ],
            [
                'key'          => 'field_water_source',
                'label'        => 'Water Source',
                'name'         => 'water_source',
                'type'         => 'text',
            ],
            [
                'key'          => 'field_still_count',
                'label'        => 'Number of Stills',
                'name'         => 'still_count',
                'type'         => 'number',
            ],
            [
                'key'          => 'field_still_types',
                'label'        => 'Still Types',
                'name'         => 'still_types',
                'type'         => 'text',
                'instructions' => 'e.g., Column stills, Pot stills',
            ],
            [
                'key'          => 'field_expressions',
                'label'        => 'Expressions',
                'name'         => 'expressions',
                'type'         => 'textarea',
                'instructions' => 'Notable expressions / brands produced',
                'rows'         => 3,
            ],
            [
                'key'          => 'field_barrel_sources',
                'label'        => 'Barrel Sources',
                'name'         => 'barrel_sources',
                'type'         => 'text',
                'instructions' => 'e.g., Oak, Ex-bourbon',
            ],
            [
                'key'          => 'field_raw_material',
                'label'        => 'Raw Material',
                'name'         => 'raw_material',
                'type'         => 'text',
                'instructions' => 'e.g., Molasses, Sugarcane juice',
            ],
            [
                'key'          => 'field_country',
                'label'        => 'Country',
                'name'         => 'country',
                'type'         => 'text',
            ],
            [
                'key'          => 'field_nom_number',
                'label'        => 'NOM Number',
                'name'         => 'nom_number',
                'type'         => 'text',
                'instructions' => 'NOM registration number (tequila)',
            ],
            [
                'key'          => 'field_cooking_method',
                'label'        => 'Cooking Method',
                'name'         => 'cooking_method',
                'type'         => 'text',
                'instructions' => 'e.g., Brick oven, Autoclave, Diffuser',
            ],
            [
                'key'          => 'field_production_capacity',
                'label'        => 'Production Capacity',
                'name'         => 'production_capacity',
                'type'         => 'text',
                'instructions' => 'e.g., Large-scale, Artisanal',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'distillery',
                ],
            ],
        ],
        'position'             => 'normal',
        'style'                => 'default',
        'label_placement'      => 'top',
        'instruction_placement'=> 'label',
    ]);
}
add_action('acf/init', 'ps_register_acf_fields');

/**
 * Fallback: Native meta boxes when ACF is not installed
 */
function ps_add_distillery_meta_boxes() {
    if (function_exists('acf_add_local_field_group')) {
        return; // ACF handles it
    }

    add_meta_box(
        'ps_distillery_details',
        'Distillery Details',
        'ps_render_distillery_meta_box',
        'distillery',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ps_add_distillery_meta_boxes');

function ps_render_distillery_meta_box($post) {
    wp_nonce_field('ps_distillery_meta', 'ps_distillery_nonce');

    $fields = [
        'latitude'           => ['label' => 'Latitude', 'type' => 'number', 'step' => '0.0001'],
        'longitude'          => ['label' => 'Longitude', 'type' => 'number', 'step' => '0.0001'],
        'distillery_status'  => ['label' => 'Status', 'type' => 'select', 'options' => ['Operating', 'Silent', 'Mothballed']],
        'distillery_type'    => ['label' => 'Type', 'type' => 'select', 'options' => ['Malt', 'Grain', 'Malt & Grain']],
        'year_founded'       => ['label' => 'Year Founded', 'type' => 'text'],
        'year_closed'        => ['label' => 'Year Closed', 'type' => 'text'],
        'official_website'   => ['label' => 'Official Website', 'type' => 'url'],
        'owner'              => ['label' => 'Owner', 'type' => 'text'],
        'water_source'       => ['label' => 'Water Source', 'type' => 'text'],
        'still_count'        => ['label' => 'Number of Stills', 'type' => 'number'],
        'still_types'        => ['label' => 'Still Types', 'type' => 'text'],
        'expressions'        => ['label' => 'Expressions', 'type' => 'text'],
        'barrel_sources'     => ['label' => 'Barrel Sources', 'type' => 'text'],
        'raw_material'       => ['label' => 'Raw Material', 'type' => 'text'],
        'country'            => ['label' => 'Country', 'type' => 'text'],
        'nom_number'         => ['label' => 'NOM Number', 'type' => 'text'],
        'cooking_method'     => ['label' => 'Cooking Method', 'type' => 'text'],
        'production_capacity'=> ['label' => 'Production Capacity', 'type' => 'text'],
    ];

    echo '<table class="form-table"><tbody>';
    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th><label for="ps_' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th>';
        echo '<td>';

        if ($field['type'] === 'select') {
            echo '<select name="ps_' . esc_attr($key) . '" id="ps_' . esc_attr($key) . '">';
            foreach ($field['options'] as $option) {
                $selected = selected($value, $option, false);
                echo '<option value="' . esc_attr($option) . '"' . $selected . '>' . esc_html($option) . '</option>';
            }
            echo '</select>';
        } else {
            $step = isset($field['step']) ? ' step="' . esc_attr($field['step']) . '"' : '';
            echo '<input type="' . esc_attr($field['type']) . '" name="ps_' . esc_attr($key) . '" id="ps_' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . $step . ' class="regular-text">';
        }

        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

function ps_save_distillery_meta($post_id) {
    if (!isset($_POST['ps_distillery_nonce']) || !wp_verify_nonce($_POST['ps_distillery_nonce'], 'ps_distillery_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'latitude', 'longitude', 'distillery_status', 'distillery_type',
        'year_founded', 'year_closed', 'official_website', 'owner',
        'water_source', 'still_count', 'still_types', 'expressions',
        'barrel_sources', 'raw_material', 'country',
        'nom_number', 'cooking_method', 'production_capacity',
    ];

    foreach ($fields as $field) {
        $key = 'ps_' . $field;
        if (isset($_POST[$key])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$key]));
        }
    }
}
add_action('save_post_distillery', 'ps_save_distillery_meta');
