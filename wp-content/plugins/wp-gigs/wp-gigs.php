<?php
/**
 * Plugin Name: WP Gigs
 * Description: Custom Gigs plugin with listing, price filtering, pagination, and settings.
 * Version: 1.1.0
 * Author: Jaya Surya
 * Text Domain: wp-gigs
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
        'default'
    );
}
add_action( 'add_meta_boxes', 'wg_add_gig_price_metabox' );

function wg_gig_price_metabox_callback( $post ) {
    wp_nonce_field( 'wg_save_gig_price', 'wg_gig_price_nonce' );
    $price = get_post_meta( $post->ID, '_wg_gig_price', true );
    ?>
    <label for="wg_gig_price"><strong><?php _e( 'Price ($)', 'wp-gigs' ); ?></strong></label>
    <input type="text" id="wg_gig_price" name="wg_gig_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%;" placeholder="e.g. 99" />
    <?php
}

function wg_save_gig_price_meta( $post_id ) {
    if ( ! isset( $_POST['wg_gig_price_nonce'] ) || ! wp_verify_nonce( $_POST['wg_gig_price_nonce'], 'wg_save_gig_price' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['wg_gig_price'] ) ) {
        $price = sanitize_text_field( $_POST['wg_gig_price'] );
        update_post_meta( $post_id, '_wg_gig_price', $price );
    }
}
add_action( 'save_post', 'wg_save_gig_price_meta' );

/**
 * Plugin Settings Page
 */
function wg_register_settings() {
    register_setting( 'wg_gigs_settings_group', 'wg_posts_per_page' );

    add_settings_section(
        'wg_main_section',
        __( 'Display Settings', 'wp-gigs' ),
        null,
        'wg-gigs-settings'
    );

    add_settings_field(
        'wg_posts_per_page',
        __( 'Default Items Per Page', 'wp-gigs' ),
        'wg_posts_per_page_callback',
        'wg-gigs-settings',
        'wg_main_section'
    );
}
add_action( 'admin_init', 'wg_register_settings' );

function wg_posts_per_page_callback() {
    $value = get_option( 'wg_posts_per_page', 10 );
    echo '<input type="number" name="wg_posts_per_page" value="' . esc_attr( $value ) . '" min="1" max="100" />';
}

function wg_add_settings_page() {
    add_options_page(
        __( 'WP Gigs Settings', 'wp-gigs' ),
        __( 'WP Gigs', 'wp-gigs' ),
        'manage_options',
        'wg-gigs-settings',
        'wg_render_settings_page'
    );
}
add_action( 'admin_menu', 'wg_add_settings_page' );

function wg_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'WP Gigs Settings', 'wp-gigs' ); ?></h1>
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

/**
 * Shortcode: [gig_list]
 * Fixed pagination with preserved filters
 */
function wg_gig_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => get_option( 'wg_posts_per_page', 10 ),
    ), $atts, 'gig_list' );

    $posts_per_page = max( 1, intval( $atts['posts_per_page'] ) );

    // Get current page properly
    $paged = max( 1, get_query_var( 'paged' ) ?: get_query_var( 'page' ) ?: 1 );

    // Get current filter values
    $min_price = isset( $_GET['min_price'] ) ? intval( $_GET['min_price'] ) : null;
    $max_price = isset( $_GET['max_price'] ) ? intval( $_GET['max_price'] ) : null;
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

    // Price filtering
    if ( $min_price !== null || $max_price !== null ) {
        $meta_query = array( 'relation' => 'AND' );
        if ( $min_price !== null ) {
            $meta_query[] = array(
                'key'     => '_wg_gig_price',
                'value'   => $min_price,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }
        if ( $max_price !== null ) {
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

        <!-- Filters Form -->
        <form method="get" class="wg-gig-filters">
            <input type="number" name="min_price" placeholder="Min Price" value="<?php echo esc_attr( $min_price ?? '' ); ?>">
            <input type="number" name="max_price" placeholder="Max Price" value="<?php echo esc_attr( $max_price ?? '' ); ?>">
            <select name="sort">
                <option value=""><?php _e( 'Default', 'wp-gigs' ); ?></option>
                <option value="latest" <?php selected( $sort, 'latest' ); ?>><?php _e( 'Latest', 'wp-gigs' ); ?></option>
                <option value="price_low" <?php selected( $sort, 'price_low' ); ?>><?php _e( 'Price: Low to High', 'wp-gigs' ); ?></option>
            </select>
            <button type="submit" class="filter_btn"><?php _e( 'Filter', 'wp-gigs' ); ?></button>
        </form>

        <div class="wg-gig-list-wrapper">
            <?php if ( $query->have_posts() ) : ?>
                <ul class="wg-gig-list">
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <li class="wg-gig-item">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </a>
                            <?php endif; ?>
                            <div class="wg-gig-body-wrapper">
                                <div class="wg-gig-body">
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <?php 
                                    $price = get_post_meta( get_the_ID(), '_wg_gig_price', true );
                                    if ( $price !== '' ) : ?>
                                        <p class="price">$<?php echo esc_html( $price ); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php 
                                // Check if content exists (strip tags and trim to avoid empty paragraphs)
                                $content = get_the_content();
                                $content_stripped = trim(wp_strip_all_tags($content));
                                ?>

                                <?php if (!empty($content_stripped)) : ?>
                                    <div class="gig-description">
                                        <?php the_content(); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <!-- FIXED PAGINATION: Preserves all GET parameters -->
                <!-- Pagination - Preserves filters AND uses correct /page/X/ format -->
<?php if ( $query->max_num_pages > 1 ) : ?>
    <nav class="wg-pagination">
        <?php
        $big = 999999999; // Big number for replacement

        echo paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => 'page/%#%',
            'current'   => max( 1, $paged ),
            'total'     => $query->max_num_pages,
            'prev_text' => '&laquo; Previous',
            'next_text' => 'Next &raquo;',
            'type'      => 'list',
            'add_args'  => array( // This preserves all GET params like min_price, sort, etc.
                'min_price' => $min_price ?? false,
                'max_price' => $max_price ?? false,
                'sort'      => $sort ?: false,
            ),
        ) );
        ?>
    </nav>
<?php endif; ?>

            <?php else : ?>
                <p><?php _e( 'No gigs found.', 'wp-gigs' ); ?></p>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'gig_list', 'wg_gig_list_shortcode' );

// ========== ADD THE AJAX CODE RIGHT HERE ==========

add_action('wp_ajax_wg_filter_gigs', 'wg_ajax_filter_gigs');
add_action('wp_ajax_nopriv_wg_filter_gigs', 'wg_ajax_filter_gigs');

function wg_ajax_filter_gigs() {
    // Use the same posts_per_page as the shortcode would (from settings or default)
    echo wg_gig_list_shortcode( array(
        'posts_per_page' => get_option('wg_posts_per_page', 10)
    ) );
    wp_die(); // Always required to end AJAX properly
}

/**
 * REST API Endpoint
 */
function wg_register_gigs_rest_api() {
    register_rest_route( 'wp-gigs/v1', '/gigs', array(
        'methods'  => 'GET',
        'callback' => 'wg_get_gigs_rest',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'wg_register_gigs_rest_api' );

function wg_get_gigs_rest( $request ) {
    $query = new WP_Query( array(
        'post_type'      => 'wg_gig',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ) );

    $data = array();
    while ( $query->have_posts() ) {
        $query->the_post();
        $data[] = array(
            'title' => get_the_title(),
            'link'  => get_permalink(),
            'price' => get_post_meta( get_the_ID(), '_wg_gig_price', true ),
            'image' => get_the_post_thumbnail_url( null, 'medium' ),
        );
    }
    wp_reset_postdata();

    return rest_ensure_response( $data );
}

/**
 * Enqueue Scripts
 */
function wg_enqueue_scripts() {
    wp_enqueue_script(
        'wg-gigs-js',
        WG_PLUGIN_URL . 'assets/js/gigs.js',
        array( 'jquery' ),
        '1.0',
        true
    );

    wp_localize_script( 'wg-gigs-js', 'wg_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wg_filter_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'wg_enqueue_scripts' );