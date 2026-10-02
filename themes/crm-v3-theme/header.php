<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php if (is_single()) : ?>

        <meta property="og:type" content="article">
        <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>">
        <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">

        <?php if (has_post_thumbnail()) : ?>
            <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'full')); ?>">
        <?php endif; ?>

        <meta property="og:description" content="<?php echo esc_attr(wp_strip_all_tags(get_the_excerpt())); ?>">

    <?php endif; ?>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<header class="site-header">

    <div class="container header-flex">

        <div class="site-branding">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img
                    src="https://cibr.com.mx/wp-content/uploads/2026/08/logo-cibr.png"
                    alt="CIBR - Centro Internacional de Bienes Raíces"
                    class="site-logo"
                >
            </a>
        </div>

        <nav class="main-navigation">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'main_menu',
                'container' => false,
                'menu_class' => 'main-menu'
            ));
            ?>
        </nav>

    </div>

</header>
