<?php
/**
 * 404 Page
 *
 * @package PowerfulScotch
 */

get_header(); ?>

<div class="content-wrap error-page">
    <div class="error-content">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Lost in the distillery</h2>
        <p class="error-message">The page you're looking for seems to have evaporated &mdash; much like the angel's share.</p>
        <div class="error-actions">
            <a href="<?php echo esc_url(home_url('/map/')); ?>" class="btn btn-primary">Explore the Map</a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-secondary">Go Home</a>
        </div>
    </div>
</div>

<?php get_footer();
