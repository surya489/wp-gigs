<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
  <div class="header-container">
    <div class="site-branding">
      <h1 class="site-title">
        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">WG Gigs</a>
      </h1>
    </div>

    <nav class="main-navigation" aria-label="Primary Navigation">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'menu_id'        => 'primary-menu',
        'container'      => false,
        'fallback_cb'    => '__return_false',
        'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
      ]);
      ?>
    </nav>
  </div>
</header>