<?php
/**
 * Single Distillery Detail Page
 *
 * @package PowerfulScotch
 */

get_header();

if (have_posts()) : the_post();
    $post_id  = get_the_ID();
    $lat      = get_post_meta($post_id, 'latitude', true);
    $lng      = get_post_meta($post_id, 'longitude', true);
    $status   = get_post_meta($post_id, 'distillery_status', true) ?: 'Operating';
    $type     = get_post_meta($post_id, 'distillery_type', true) ?: 'Malt';
    $year     = get_post_meta($post_id, 'year_founded', true);
    $year_closed = get_post_meta($post_id, 'year_closed', true);
    $website  = get_post_meta($post_id, 'official_website', true);
    $owner    = get_post_meta($post_id, 'owner', true);
    $water    = get_post_meta($post_id, 'water_source', true);
    $stills   = get_post_meta($post_id, 'still_count', true);

    $regions      = wp_get_post_terms($post_id, 'region', ['fields' => 'names']);
    $region_name  = !empty($regions) ? $regions[0] : '';
    $spirit_types = wp_get_post_terms($post_id, 'spirit_type', ['fields' => 'names']);
    $spirit_name  = !empty($spirit_types) ? $spirit_types[0] : 'Scotch';
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
        <a href="<?php echo esc_url(home_url('/map/?spirit=' . $spirit_slug)); ?>"><?php echo esc_html($spirit_name); ?></a>
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
                <span class="meta-tag"><?php echo esc_html($type); ?></span>
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
                <tr>
                    <th>Region</th>
                    <td><?php echo esc_html($region_name ?: 'Scotland'); ?></td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td><?php echo esc_html($type); ?></td>
                </tr>
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
