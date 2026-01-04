<?php
/**
 * Plugin Name: WP Gigs
 * Description: Custom Gigs marketplace with listing, price filtering, sorting, pagination, settings, and REST API.
 * Version: 1.2.0
 * Author: Jaya Surya
 * Text Domain: wp-gigs
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register Custom Post Type: Gigs
 */
function wg_register_gigs_cpt() {
    $labels = array(
        'name'                  => _x( 'Gigs', 'Post Type General Name', 'wp-gigs' ),
        'singular_name'         => _x( 'Gig', 'Post Type Singular Name', 'wp-gigs' ),
        'menu_name'             => __( 'Gigs', 'wp-gigs' ),
        'add_new'               => __( 'Add New', 'wp-gigs' ),
        'add_new_item'          => __( 'Add New Gig', 'wp-gigs' ),
        'edit_item'             => __( 'Edit Gig', 'wp-gigs' ),
        'all_items'             => __( 'All Gigs', 'wp-gigs' ),
        'search_items'          => __( 'Search Gigs', 'wp-gigs' ),
        'not_found'             => __( 'No gigs found.', 'wp-gigs' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'gigs' ),
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-awards',
        'menu_position'      => 5,
    );

    register_post_type( 'wg_gig', $args );
}
add_action( 'init', 'wg_register_gigs_cpt' );

/**
 * Add Gig Price Meta Box
 */
function wg_add_gig_price_metabox() {
    add_meta_box(
        'wg_gig_price_metabox',
        __( 'Gig Price', 'wp-gigs' ),
        'wg_gig_price_metabox_callback',
        'wg_gig',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wg_add_gig_price_metabox' );

function wg_gig_price_metabox_callback( $post ) {
    wp_nonce_field( 'wg_save_gig_price', 'wg_gig_price_nonce' );
    $price = get_post_meta( $post->ID, '_wg_gig_price', true );
    ?>
    <p>
        <label for="wg_gig_price"><strong><?php _e( 'Price ($)', 'wp-gigs' ); ?></strong></label><br>
        <input type="number" id="wg_gig_price" name="wg_gig_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%;" min="0" step="1" placeholder="e.g. 99" />
    </p>
    <?php
}

function wg_save_gig_price_meta( $post_id ) {
    if ( ! isset( $_POST['wg_gig_price_nonce'] ) || ! wp_verify_nonce( $_POST['wg_gig_price_nonce'], 'wg_save_gig_price' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['wg_gig_price'] ) ) {
        $price = absint( $_POST['wg_gig_price'] ); // Better: force integer
        update_post_meta( $post_id, '_wg_gig_price', $price );
    } else {
        delete_post_meta( $post_id, '_wg_gig_price' );
    }
}
add_action( 'save_post_wg_gig', 'wg_save_gig_price_meta' ); // More specific hook

/**
 * Plugin Settings Page
 */
function wg_register_settings() {
    register_setting( 'wg_gigs_settings_group', 'wg_posts_per_page', 'absint' );

    add_settings_section(
        'wg_main_section',
        __( 'Display Settings', 'wp-gigs' ),
        '__return_false',
        'wg-gigs-settings'
    );

    add_settings_field(
        'wg_posts_per_page',
        __( 'Gigs Per Page', 'wp-gigs' ),
        'wg_posts_per_page_callback',
        'wg-gigs-settings',
        'wg_main_section'
    );
}
add_action( 'admin_init', 'wg_register_settings' );

function wg_posts_per_page_callback() {
    $value = get_option( 'wg_posts_per_page', 12 );
    echo '<input type="number" name="wg_posts_per_page" value="' . esc_attr( $value ) . '" min="1" max="100" />';
    echo '<p class="description">' . __( 'Default number of gigs shown per page in [gig_list].', 'wp-gigs' ) . '</p>';
}

function wg_add_settings_page() {
    add_options_page(
        'WP Gigs Settings',
        'WP Gigs',
        'manage_options',
        'wg-gigs-settings',
        'wg_render_settings_page'
    );
}
add_action( 'admin_menu', 'wg_add_settings_page' );

function wg_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'WP Gigs Settings', 'wp-gigs' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wg_gigs_settings_group' );
            do_settings_sections( 'wg-gigs-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Shortcode: [gig_list]
 */
function wg_gig_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => get_option( 'wg_posts_per_page', 12 ),
    ), $atts, 'gig_list' );

    $posts_per_page = max( 1, intval( $atts['posts_per_page'] ) );
    $paged = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) );

    // Filters from GET
    $min_price = isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : null;
    $max_price = isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : null;
    $sort      = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : '';

    $args = array(
        'post_type'      => 'wg_gig',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
    );

    // Sorting
    if ( $sort === 'latest' ) {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    } elseif ( $sort === 'price_low' ) {
        $args['meta_key'] = '_wg_gig_price';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'ASC';
    }

    // Price range filter
    if ( $min_price || $max_price ) {
        $meta_query = array( 'relation' => 'AND' );
        if ( $min_price ) {
            $meta_query[] = array(
                'key'     => '_wg_gig_price',
                'value'   => $min_price,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }
        if ( $max_price ) {
            $meta_query[] = array(
                'key'     => '_wg_gig_price',
                'value'   => $max_price,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query( $args );

    ob_start();
    ?>
    <div class="wg-gig-container">

        <form method="get" class="wg-gig-filters" id="wg-gig-filters-form">
            <input type="number" name="min_price" placeholder="<?php _e('Min Price', 'wp-gigs'); ?>" value="<?php echo esc_attr($min_price ?? ''); ?>">
            <input type="number" name="max_price" placeholder="<?php _e('Max Price', 'wp-gigs'); ?>" value="<?php echo esc_attr($max_price ?? ''); ?>">
            <select name="sort">
                <option value=""><?php _e( 'Sort By', 'wp-gigs' ); ?></option>
                <option value="latest" <?php selected( $sort, 'latest' ); ?>><?php _e( 'Latest First', 'wp-gigs' ); ?></option>
                <option value="price_low" <?php selected( $sort, 'price_low' ); ?>><?php _e( 'Price: Low to High', 'wp-gigs' ); ?></option>
            </select>
            <button type="submit" class="filter_btn"><?php _e( 'Apply', 'wp-gigs' ); ?></button>
        </form>

        <div id="wg-gig-list-results">
            <?php if ( $query->have_posts() ) : ?>
                <ul class="wg-gig-list">
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <li class="wg-gig-item">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium', ['class' => 'gig-thumb'] ); ?>
                                </a>
                            <?php endif; ?>
                            <div class="wg-gig-body-wrapper">
                                <div class="wg-gig-body">
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <?php 
                                    $price = get_post_meta( get_the_ID(), '_wg_gig_price', true );
                                    if ( $price !== '' ) : ?>
                                        <p class="price">Starting at $<strong><?php echo esc_html( $price ); ?></strong></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <?php if ( $query->max_num_pages > 1 ) : ?>
                    <nav class="wg-pagination">
                        <?php
                        $big = 999999999;
                        echo paginate_links( array(
                            'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                            'format'  => 'page/%#%',
                            'current' => $paged,
                            'total'   => $query->max_num_pages,
                            'prev_text' => '&laquo; Previous',
                            'next_text' => 'Next &raquo;',
                            'type'     => 'list',
                            'add_args' => array(
                                'min_price' => $min_price ?? false,
                                'max_price' => $max_price ?? false,
                                'sort'      => $sort ?: false,
                            ),
                        ) );
                        ?>
                    </nav>
                <?php endif; ?>

            <?php else : ?>
                <p><?php _e( 'No gigs found matching your criteria.', 'wp-gigs' ); ?></p>
            <?php endif; wp_reset_postdata(); ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'gig_list', 'wg_gig_list_shortcode' );

/**
 * AJAX Handler for Live Filtering
 */
add_action( 'wp_ajax_wg_filter_gigs', 'wg_ajax_filter_gigs' );
add_action( 'wp_ajax_nopriv_wg_filter_gigs', 'wg_ajax_filter_gigs' );

function wg_ajax_filter_gigs() {
    // Recreate shortcode output with current GET params
    echo wg_gig_list_shortcode( array(
        'posts_per_page' => get_option( 'wg_posts_per_page', 12 )
    ) );
    wp_die();
}

/**
 * REST API Endpoint: /wp-json/wp-gigs/v1/gigs
 */
function wg_register_gigs_rest_api() {
    register_rest_route( 'wp-gigs/v1', '/gigs', array(
        'methods'             => 'GET',
        'callback'            => 'wg_get_gigs_rest',
        'permission_callback' => '__return_true',
        'args'                 => array(
            'per_page' => array(
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ),
            'page' => array(
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'wg_register_gigs_rest_api' );

function wg_get_gigs_rest( $request ) {
    $per_page = $request->get_param( 'per_page' );
    $page     = $request->get_param( 'page' );

    $args = array(
        'post_type'      => 'wg_gig',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
    );

    $query = new WP_Query( $args );
    $gigs  = array();

    while ( $query->have_posts() ) {
        $query->the_post();
        $gigs[] = array(
            'id'       => get_the_ID(),
            'title'    => get_the_title(),
            'link'     => get_permalink(),
            'price'    => get_post_meta( get_the_ID(), '_wg_gig_price', true ),
            'image'    => get_the_post_thumbnail_url( null, 'medium' ),
            'excerpt'  => get_the_excerpt(),
        );
    }
    wp_reset_postdata();

    return rest_ensure_response( array(
        'gigs'        => $gigs,
        'total'       => $query->found_posts,
        'pages'       => $query->max_num_pages,
        'current_page'=> $page,
    ) );
}

/**
 * Enqueue Scripts
 */
function wg_enqueue_scripts() {
    wp_enqueue_script(
        'wg-gigs-js',
        WG_PLUGIN_URL . 'assets/js/gigs.js',
        array( 'jquery' ),
        '1.2',
        true
    );

    wp_localize_script( 'wg-gigs-js', 'wgAjax', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'wg_filter_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'wg_enqueue_scripts' );