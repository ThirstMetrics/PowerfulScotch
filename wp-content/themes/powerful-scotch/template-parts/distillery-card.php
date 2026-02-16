<?php
/**
 * Template Part: Distillery Card
 *
 * @package PowerfulScotch
 */

$post_id = get_the_ID();
$status  = get_post_meta($post_id, 'distillery_status', true) ?: 'Operating';
$type    = get_post_meta($post_id, 'distillery_type', true) ?: 'Malt';
$year    = get_post_meta($post_id, 'year_founded', true);
$regions = wp_get_post_terms($post_id, 'region', ['fields' => 'names']);
$region  = !empty($regions) ? $regions[0] : '';
$status_class = sanitize_html_class(strtolower($status));
?>

<article <?php post_class('distillery-card'); ?>>
    <a href="<?php the_permalink(); ?>" class="distillery-card__link">
        <div class="distillery-card__header">
            <h3 class="distillery-card__name"><?php the_title(); ?></h3>
            <span class="status-badge status-badge--<?php echo $status_class; ?> status-badge--sm"><?php echo esc_html($status); ?></span>
        </div>
        <div class="distillery-card__meta">
            <?php if ($region) : ?>
                <span class="meta-tag meta-tag--sm"><?php echo esc_html($region); ?></span>
            <?php endif; ?>
            <span class="meta-tag meta-tag--sm"><?php echo esc_html($type); ?></span>
            <?php if ($year) : ?>
                <span class="distillery-card__year">Est. <?php echo esc_html($year); ?></span>
            <?php endif; ?>
        </div>
    </a>
</article>
