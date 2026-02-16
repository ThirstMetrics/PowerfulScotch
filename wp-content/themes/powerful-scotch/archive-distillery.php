<?php
/**
 * Archive: Distillery Listing
 *
 * @package PowerfulScotch
 */

get_header(); ?>

<div class="content-wrap">
    <div class="archive-header">
        <h1 class="archive-title">All Distilleries</h1>
        <p class="archive-desc">Browse our complete directory of distilleries. Click any name to view details.</p>
        <a href="<?php echo esc_url(home_url('/map/')); ?>" class="btn btn-secondary">View on Map</a>
    </div>

    <?php if (have_posts()) : ?>
    <div class="distillery-grid">
        <?php while (have_posts()) : the_post();
            get_template_part('template-parts/distillery-card');
        endwhile; ?>
    </div>

    <div class="pagination">
        <?php the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => '&larr; Previous',
            'next_text' => 'Next &rarr;',
        ]); ?>
    </div>

    <?php else : ?>
    <div class="no-content">
        <h2>No distilleries found</h2>
        <p>Check back soon &mdash; we're adding new distilleries regularly.</p>
    </div>
    <?php endif; ?>
</div>

<?php get_footer();
