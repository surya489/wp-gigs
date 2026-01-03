<?php
/**
 * Plugin Name: WP Gigs
 * Description: Custom Gigs plugin.
 * Version: 1.0.0
 * Author: Jaya Surya
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Custom Post Type: Gigs
 */
function wg_register_gigs_cpt() {

    $labels = array(
        'name'                  => _x( 'Gigs', 'Post Type General Name', 'wp-gigs' ),
        'singular_name'         => _x( 'Gig', 'Post Type Singular Name', 'wp-gigs' ),
        'menu_name'             => __( 'Gigs', 'wp-gigs' ),
        'name_admin_bar'        => __( 'Gig', 'wp-gigs' ),
        'add_new'               => __( 'Add New', 'wp-gigs' ),
        'add_new_item'          => __( 'Add New Gig', 'wp-gigs' ),
        'edit_item'             => __( 'Edit Gig', 'wp-gigs' ),
        'new_item'              => __( 'New Gig', 'wp-gigs' ),
        'view_item'             => __( 'View Gig', 'wp-gigs' ),
        'all_items'             => __( 'All Gigs', 'wp-gigs' ),
        'search_items'          => __( 'Search Gigs', 'wp-gigs' ),
        'not_found'             => __( 'No gigs found.', 'wp-gigs' ),
        'not_found_in_trash'    => __( 'No gigs found in Trash.', 'wp-gigs' ),
    );

    $args = array(
        'label'                 => __( 'Gig', 'wp-gigs' ),
        'description'           => __( 'Custom Post Type for Gigs', 'wp-gigs' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ), // Featured Image + Title + Description
        'public'                => true,
        'show_in_rest'          => true, // important for REST API
        'has_archive'           => true,
        'rewrite'               => array( 'slug' => 'gigs' ),
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-awards',
    );

    register_post_type( 'wg_gig', $args );
}
add_action( 'init', 'wg_register_gigs_cpt' );

/**
 * Add Meta Box for Gig Price
 */
function wg_add_gig_price_metabox() {
    add_meta_box(
        'wg_gig_price_metabox',       // ID
        __( 'Gig Price', 'wp-gigs' ), // Title
        'wg_gig_price_metabox_callback', // Callback function
        'wg_gig',                     // Post type
        'side',                        // Context
        'default'                      // Priority
    );
}
add_action( 'add_meta_boxes', 'wg_add_gig_price_metabox' );

/**
 * Meta Box Callback
 */
function wg_gig_price_metabox_callback( $post ) {
    // Add nonce for security
    wp_nonce_field( 'wg_save_gig_price', 'wg_gig_price_nonce' );

    // Get existing price if available
    $price = get_post_meta( $post->ID, '_wg_gig_price', true );

    echo '<label for="wg_gig_price">' . __( 'Price ($)', 'wp-gigs' ) . '</label>';
    echo '<input type="text" id="wg_gig_price" name="wg_gig_price" value="' . esc_attr( $price ) . '" style="width:100%;" />';
}

/**
 * Save Meta Box Value
 */
function wg_save_gig_price_meta( $post_id ) {

    // Check if nonce is set
    if ( ! isset( $_POST['wg_gig_price_nonce'] ) ) {
        return;
    }

    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['wg_gig_price_nonce'], 'wg_save_gig_price' ) ) {
        return;
    }

    // Avoid autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check user permission
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Sanitize and save price
    if ( isset( $_POST['wg_gig_price'] ) ) {
        $price = sanitize_text_field( $_POST['wg_gig_price'] );
        update_post_meta( $post_id, '_wg_gig_price', $price );
    }
}
add_action( 'save_post', 'wg_save_gig_price_meta' );

/**
 * Shortcode [gig_list]
 */
function wg_gig_list_shortcode( $atts ) {

    // Attributes with default values
    $atts = shortcode_atts( array(
        'posts_per_page' => 5,
        'paged'          => 1,
    ), $atts, 'gig_list' );

    $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

    $args = array(
        'post_type'      => 'wg_gig',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'paged'          => $paged,
        'post_status'    => 'publish',
    );

    $query = new WP_Query( $args );

    $gigs = $query->posts;

    // Start output buffering
    ob_start();

    // Load template
    include plugin_dir_path( __FILE__ ) . 'template-parts/gigs-list.php';

    // Pagination
    $big = 999999999;
    if ( $query->max_num_pages > 1 ) {
        echo '<div class="wg-pagination">';
        echo paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '?paged=%#%',
            'current'   => max( 1, $paged ),
            'total'     => $query->max_num_pages,
        ) );
        echo '</div>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode( 'gig_list', 'wg_gig_list_shortcode' );