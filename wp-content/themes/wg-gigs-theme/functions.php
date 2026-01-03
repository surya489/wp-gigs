<?php

function wg_theme_assets() {
    wp_enqueue_style(
        'wg-style',
        get_stylesheet_uri(),
        [],
        '1.0'
    );
}
add_action('wp_enqueue_scripts', 'wg_theme_assets');

add_theme_support('post-thumbnails');
add_theme_support('title-tag');
