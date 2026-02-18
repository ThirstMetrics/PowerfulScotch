<?php
/**
 * Single Distillery Detail Page
 *
 * @package PowerfulSpirits
 */

get_header();

if (have_posts()) : the_post();
    $post_id  = get_the_ID();
    $lat      = get_post_meta($post_id, 'latitude', true);
    $lng      = get_post_meta($post_id, 'longitude', true);
    $status   = get_post_meta($post_id, 'distillery_status', true) ?: 'Operating';
    $type     = get_post_meta($post_id, 'distillery_type', true);
    $year     = get_post_meta($post_id, 'year_founded', true);
    $year_closed = get_post_meta($post_id, 'year_closed', true);
    $website  = get_post_meta($post_id, 'official_website', true);
    $owner    = get_post_meta($post_id, 'owner', true);
    $water    = get_post_meta($post_id, 'water_source', true);
    $stills   = get_post_meta($post_id, 'still_count', true);
    $still_types    = get_post_meta($post_id, 'still_types', true);
    $expressions    = get_post_meta($post_id, 'expressions', true);
    $barrel_sources = get_post_meta($post_id, 'barrel_sources', true);
    $raw_material   = get_post_meta($post_id, 'raw_material', true);
    $country        = get_post_meta($post_id, 'country', true);
    $nom_number          = get_post_meta($post_id, 'nom_number', true);
    $cooking_method      = get_post_meta($post_id, 'cooking_method', true);
    $production_capacity = get_post_meta($post_id, 'production_capacity', true);
    $name_japanese       = get_post_meta($post_id, 'name_japanese', true);
    $prefecture          = get_post_meta($post_id, 'prefecture', true);
    $key_brands          = get_post_meta($post_id, 'key_brands', true);
    $rice_varieties      = get_post_meta($post_id, 'rice_varieties', true);
    $toji_school         = get_post_meta($post_id, 'toji_school', true);
    $production_size     = get_post_meta($post_id, 'production_size', true);

    $regions      = wp_get_post_terms($post_id, 'region', ['fields' => 'names']);
    $region_name  = !empty($regions) ? $regions[0] : '';
    $spirit_types = wp_get_post_terms($post_id, 'spirit_type', ['fields' => 'names']);
    $spirit_name  = !empty($spirit_types) ? $spirit_types[0] : '';
    $spirit_slug  = !empty($spirit_types) ? sanitize_title($spirit_types[0]) : 'scotch';

    $year_display = $year;
    if ($year_closed) {
        $year_display .= ' &ndash; ' . $year_closed;
    } elseif ($year) {
        $year_display .= ' &ndash; present';
    }

    $status_class = sanitize_html_class(strtolower($status));
?>

<div class="distillery-detail">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
        <span class="breadcrumb-sep">/</span>
        <?php if ($spirit_name) : ?>
        <a href="<?php echo esc_url(home_url('/map/?spirit=' . $spirit_slug)); ?>"><?php echo esc_html($spirit_name); ?></a>
        <?php endif; ?>
        <?php if ($region_name) : ?>
            <span class="breadcrumb-sep">/</span>
            <span><?php echo esc_html($region_name); ?></span>
        <?php endif; ?>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current"><?php the_title(); ?></span>
    </nav>

    <div class="distillery-header">
        <div class="distillery-header__text">
            <h1 class="distillery-name"><?php the_title(); ?></h1>
            <div class="distillery-meta">
                <span class="status-badge status-badge--<?php echo $status_class; ?>"><?php echo esc_html($status); ?></span>
                <?php if ($region_name) : ?>
                    <span class="meta-tag"><?php echo esc_html($region_name); ?></span>
                <?php endif; ?>
                <?php if ($type) : ?><span class="meta-tag"><?php echo esc_html($type); ?></span><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="distillery-body">
        <div class="distillery-map-col">
            <div class="detail-map" id="detail-map"></div>
            <a href="<?php echo esc_url(add_query_arg(['spirit' => $spirit_slug, 'distillery' => get_post_field('post_name')], home_url('/map/'))); ?>" class="btn btn-primary btn-back-to-map">
                &larr; View on Full Map
            </a>
        </div>

        <div class="distillery-info-col">
            <table class="facts-table">
                <?php if ($year_display) : ?>
                <tr>
                    <th>Years Active</th>
                    <td><?php echo $year_display; ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($region_name) : ?>
                <tr>
                    <th>Region</th>
                    <td><?php echo esc_html($region_name); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($country) : ?>
                <tr>
                    <th>Country</th>
                    <td><?php echo esc_html($country); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($nom_number) : ?>
                <tr>
                    <th>NOM Number</th>
                    <td><?php echo esc_html($nom_number); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($name_japanese) : ?>
                <tr>
                    <th>Japanese Name</th>
                    <td><?php echo esc_html($name_japanese); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($prefecture) : ?>
                <tr>
                    <th>Prefecture</th>
                    <td><?php echo esc_html($prefecture); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($type) : ?>
                <tr>
                    <th>Type</th>
                    <td><?php echo esc_html($type); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Status</th>
                    <td><span class="status-dot status-dot--<?php echo $status_class; ?>"></span> <?php echo esc_html($status); ?></td>
                </tr>
                <?php if ($owner) : ?>
                <tr>
                    <th>Owner</th>
                    <td><?php echo esc_html($owner); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($water) : ?>
                <tr>
                    <th>Water Source</th>
                    <td><?php echo esc_html($water); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($stills) : ?>
                <tr>
                    <th>Stills</th>
                    <td><?php echo esc_html($stills); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($still_types) : ?>
                <tr>
                    <th>Still Types</th>
                    <td><?php echo esc_html($still_types); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($cooking_method) : ?>
                <tr>
                    <th>Cooking Method</th>
                    <td><?php echo esc_html($cooking_method); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($raw_material) : ?>
                <tr>
                    <th>Raw Material</th>
                    <td><?php echo esc_html($raw_material); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($production_capacity) : ?>
                <tr>
                    <th>Production Capacity</th>
                    <td><?php echo esc_html($production_capacity); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($barrel_sources) : ?>
                <tr>
                    <th>Barrel Sources</th>
                    <td><?php echo esc_html($barrel_sources); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($rice_varieties) : ?>
                <tr>
                    <th>Rice Varieties</th>
                    <td><?php echo esc_html($rice_varieties); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($toji_school) : ?>
                <tr>
                    <th>Toji School</th>
                    <td><?php echo esc_html($toji_school); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($production_size) : ?>
                <tr>
                    <th>Production Size</th>
                    <td><?php echo esc_html($production_size); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($key_brands) : ?>
                <tr>
                    <th>Key Brands</th>
                    <td><?php echo esc_html($key_brands); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($expressions) : ?>
                <tr>
                    <th>Expressions</th>
                    <td><?php echo esc_html($expressions); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($website) : ?>
                <tr>
                    <th>Website</th>
                    <td><a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(preg_replace('#^https?://(www\.)?#', '', $website)); ?></a></td>
                </tr>
                <?php endif; ?>
                <?php if ($lat && $lng) : ?>
                <tr>
                    <th>Coordinates</th>
                    <td><?php echo esc_html(round((float)$lat, 4) . ', ' . round((float)$lng, 4)); ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <?php if (get_the_content()) : ?>
            <div class="distillery-description">
                <h2>About <?php the_title(); ?></h2>
                <?php the_content(); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif;

get_footer();
