<?php
/**
 * Plugin Name: WP Gigs
 * Plugin URI:  https://jayasurya-portfolio-new.vercel.app/
 * Description: Custom Gigs marketplace with listing, price filtering, sorting, pagination, settings, and REST API.
 * Version:     1.0.0
 * Author:      Jaya Surya
 * Author URI:  https://jayasurya-portfolio-new.vercel.app
 * License:     GPL-2.0+
 * Text Domain: wp-gigs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register the 'gig' custom post type
function wg_register_gig_cpt() {
    $labels = array(
        'name'               => __( 'Gigs', 'wp-gigs' ),
        'singular_name'      => __( 'Gig', 'wp-gigs' ),
        'menu_name'          => __( 'Gigs', 'wp-gigs' ),
        'add_new'            => __( 'Add New', 'wp-gigs' ),
        'add_new_item'       => __( 'Add New Gig', 'wp-gigs' ),
        'edit_item'          => __( 'Edit Gig', 'wp-gigs' ),
        'new_item'           => __( 'New Gig', 'wp-gigs' ),
        'view_item'          => __( 'View Gig', 'wp-gigs' ),
        'search_items'       => __( 'Search Gigs', 'wp-gigs' ),
        'not_found'          => __( 'No gigs found', 'wp-gigs' ),
        'not_found_in_trash' => __( 'No gigs found in Trash', 'wp-gigs' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-performance',
    );

    register_post_type( 'gig', $args );
}
add_action( 'init', 'wg_register_gig_cpt' );

// Add price meta box in admin
function wg_add_gig_price_meta_box() {
    add_meta_box(
        'wg_gig_price',
        __( 'Gig Price', 'wp-gigs' ),
        'wg_render_gig_price_meta_box',
        'gig',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wg_add_gig_price_meta_box' );

function wg_render_gig_price_meta_box( $post ) {
    wp_nonce_field( 'wg_save_gig_price', 'wg_gig_price_nonce' );

    $price = get_post_meta( $post->ID, '_wg_gig_price', true );
    ?>
    <p>
        <label for="wg_gig_price"><strong><?php _e( 'Price ($)', 'wp-gigs' ); ?></strong></label><br>
        <input type="number" id="wg_gig_price" name="wg_gig_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" style="width:100%;" placeholder="e.g. 99.99">
    </p>
    <?php
}

// Save the price securely
function wg_save_gig_price_meta( $post_id ) {
    if ( ! isset( $_POST['wg_gig_price_nonce'] ) || ! wp_verify_nonce( $_POST['wg_gig_price_nonce'], 'wg_save_gig_price' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['wg_gig_price'] ) ) {
        $price = sanitize_text_field( $_POST['wg_gig_price'] );
        update_post_meta( $post_id, '_wg_gig_price', $price );
    } else {
        delete_post_meta( $post_id, '_wg_gig_price' );
    }
}
add_action( 'save_post', 'wg_save_gig_price_meta' );

// Settings page for items per page
function wg_register_settings() {
    register_setting( 'wg_settings_group', 'wg_items_per_page', 'absint' );

    add_settings_section( 'wg_general_section', __( 'General Settings', 'wp-gigs' ), '__return_false', 'wg_settings' );

    add_settings_field(
        'wg_items_per_page',
        __( 'Gigs per page in listing', 'wp-gigs' ),
        'wg_items_per_page_callback',
        'wg_settings',
        'wg_general_section'
    );
}
add_action( 'admin_init', 'wg_register_settings' );

function wg_items_per_page_callback() {
    $value = get_option( 'wg_items_per_page', 10 );
    echo '<input type="number" name="wg_items_per_page" value="' . esc_attr( $value ) . '" min="1" />';
}

function wg_add_settings_page() {
    add_options_page(
        'WP Gigs Settings',
        'WP Gigs',
        'manage_options',
        'wg_settings',
        'wg_render_settings_page'
    );
}
add_action( 'admin_menu', 'wg_add_settings_page' );

function wg_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP Gigs Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wg_settings_group' );
            do_settings_sections( 'wg_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Enqueue custom stylesheet
function wg_enqueue_styles() {
    wp_enqueue_style( 'wp-gigs-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'wg_enqueue_styles' );

// Shortcode to display gigs list with pagination
function wg_gig_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'per_page' => get_option( 'wg_items_per_page', 10 ),
    ), $atts, 'gig_list' );

    $paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

    $query = new WP_Query( array(
        'post_type'      => 'gig',
        'posts_per_page' => intval( $atts['per_page'] ),
        'post_status'    => 'publish',
        'paged'          => $paged,
    ) );

    ob_start();

    $template_path = plugin_dir_path( __FILE__ ) . 'template-parts/gigs-list.php';

    if ( file_exists( $template_path ) ) {
        include $template_path;
    } else {
        echo '<p style="color:red;">Error: Template file not found at ' . esc_html( $template_path ) . '</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'gig_list', 'wg_gig_list_shortcode' );

// Custom REST API endpoint
function wg_register_rest_endpoint() {
    register_rest_route( 'wp-gigs/v1', '/gigs', array(
        'methods'  => 'GET',
        'callback' => 'wg_get_gigs_data',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'wg_register_rest_endpoint' );

function wg_get_gigs_data( $request ) {
    $query = new WP_Query( array(
        'post_type'      => 'gig',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ) );

    $gigs = array();

    while ( $query->have_posts() ) {
        $query->the_post();
        $gigs[] = array(
            'title' => get_the_title(),
            'link'  => get_permalink(),
            'price' => get_post_meta( get_the_ID(), '_wg_gig_price', true ),
        );
    }

    wp_reset_postdata();

    return rest_ensure_response( $gigs );
}

// Create "Gigs" page on plugin activation
function wg_create_gigs_page() {
    $page_title = 'Gigs';
    $page_slug  = 'gigs';

    if ( ! get_page_by_path( $page_slug ) ) {
        wp_insert_post( array(
            'post_title'   => $page_title,
            'post_content' => '[gig_list]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $page_slug,
        ) );
    }
}
register_activation_hook( __FILE__, 'wg_create_gigs_page' );