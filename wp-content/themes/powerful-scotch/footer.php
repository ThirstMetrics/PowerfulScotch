</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/powerfulthirst-logo.png'); ?>" alt="PowerfulThirst Atlas" class="site-logo__img">
            <span class="logo-text">Powerful<span class="logo-accent">Spirits</span></span>
            <p class="footer-tagline">Mapping the world's finest distilleries</p>
        </div>
        <div class="footer-links">
            <a href="<?php echo esc_url(home_url('/map/?spirit=scotch')); ?>">Scotch Map</a>
            <a href="<?php echo esc_url(home_url('/map/?spirit=tequila')); ?>">Tequila Map</a>
            <a href="<?php echo esc_url(home_url('/map/?spirit=rum')); ?>">Rum Map</a>
            <a href="<?php echo esc_url(home_url('/map/?spirit=sake')); ?>">Sake Map</a>
        </div>
        <div class="footer-meta">
            <p>&copy; <?php echo date('Y'); ?> <a href="https://powerfulthirst.com">PowerfulThirst</a>. Distillery data inspired by <a href="https://www.maltmadness.com" rel="noopener noreferrer" target="_blank">Malt Madness</a>.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
