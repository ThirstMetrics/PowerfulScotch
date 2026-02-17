<?php
/**
 * Fallback template
 *
 * @package PowerfulSpirits
 */

get_header(); ?>

<div class="content-wrap">
    <?php if (have_posts()) : ?>
        <div class="posts-grid">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('post-card'); ?>>
                    <h2 class="post-card__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <div class="post-card__excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php the_posts_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="no-content">
            <h1>Nothing found</h1>
            <p>Try searching or browse our <a href="<?php echo esc_url(home_url('/map/')); ?>">distillery map</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer();
