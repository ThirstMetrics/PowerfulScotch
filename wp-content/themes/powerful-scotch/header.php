<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php bloginfo('description'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" id="site-header">
    <div class="header-inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
            <span class="logo-text">Powerful<span class="logo-accent">Scotch</span></span>
        </a>

        <nav class="spirit-nav" id="spirit-nav" aria-label="Spirit categories">
            <?php
            $spirits = [
                'scotch'  => 'Scotch',
                'tequila' => 'Tequila',
                'rum'     => 'Rum',
                'sake'    => 'Sake',
            ];
            $current_spirit = isset($_GET['spirit']) ? sanitize_key($_GET['spirit']) : 'scotch';
            $map_url = home_url('/map/');

            foreach ($spirits as $slug => $label) :
                $is_active = ($current_spirit === $slug);
                $url = add_query_arg('spirit', $slug, $map_url);
            ?>
                <a href="<?php echo esc_url($url); ?>"
                   class="spirit-tab <?php echo $is_active ? 'active' : ''; ?>"
                   data-spirit="<?php echo esc_attr($slug); ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="header-link">About</a>
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
        <?php foreach ($spirits as $slug => $label) :
            $is_active = ($current_spirit === $slug);
            $url = add_query_arg('spirit', $slug, $map_url);
        ?>
            <a href="<?php echo esc_url($url); ?>"
               class="mobile-spirit-tab <?php echo $is_active ? 'active' : ''; ?>"
               data-spirit="<?php echo esc_attr($slug); ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
        <a href="<?php echo esc_url(home_url('/about/')); ?>" class="mobile-spirit-tab">About</a>
    </nav>
</header>

<main class="site-main" id="site-main">
