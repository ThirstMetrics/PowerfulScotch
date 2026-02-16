<?php
/**
 * Template Part: Map Region Filter Chips
 *
 * @package PowerfulScotch
 */

$spirit = isset($_GET['spirit']) ? sanitize_key($_GET['spirit']) : 'scotch';

$regions = get_terms([
    'taxonomy'   => 'region',
    'hide_empty' => true,
    'orderby'    => 'name',
]);
?>

<div class="filter-chips" role="group" aria-label="Filter by region">
    <button class="filter-chip active" data-region="all">All</button>
    <?php if (!is_wp_error($regions)) :
        foreach ($regions as $region) : ?>
            <button class="filter-chip" data-region="<?php echo esc_attr($region->slug); ?>">
                <?php echo esc_html($region->name); ?>
                <span class="filter-chip__count"><?php echo esc_html($region->count); ?></span>
            </button>
        <?php endforeach;
    endif; ?>
</div>
