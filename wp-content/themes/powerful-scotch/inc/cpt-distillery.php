<?php
/**
 * Custom Post Type: Distillery
 *
 * @package PowerfulSpirits
 */

defined('ABSPATH') || exit;

function ps_register_distillery_cpt() {
    $labels = [
        'name'                  => 'Distilleries',
        'singular_name'         => 'Distillery',
        'add_new'               => 'Add New Distillery',
        'add_new_item'          => 'Add New Distillery',
        'edit_item'             => 'Edit Distillery',
        'new_item'              => 'New Distillery',
        'view_item'             => 'View Distillery',
        'view_items'            => 'View Distilleries',
        'search_items'          => 'Search Distilleries',
        'not_found'             => 'No distilleries found',
        'not_found_in_trash'    => 'No distilleries found in Trash',
        'all_items'             => 'All Distilleries',
        'archives'              => 'Distillery Archives',
        'attributes'            => 'Distillery Attributes',
        'insert_into_item'      => 'Insert into distillery',
        'uploaded_to_this_item' => 'Uploaded to this distillery',
        'menu_name'             => 'Distilleries',
    ];

    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'query_var'           => true,
        'rewrite'             => ['slug' => 'distillery', 'with_front' => false],
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-location',
        'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'taxonomies'          => ['spirit_type', 'region'],
    ];

    register_post_type('distillery', $args);
}
add_action('init', 'ps_register_distillery_cpt');
