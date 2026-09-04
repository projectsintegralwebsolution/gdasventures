<?php
/**
 * PixelPierNYC Child - G Das Ventures Functions
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

add_action( 'wp_enqueue_scripts', 'gdas_enqueue_scripts', 20 );
function gdas_enqueue_scripts() {
    // Google Fonts: Playfair Display + Plus Jakarta Sans
    wp_enqueue_style(
        'gdas-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // FontAwesome 6 for Icons
    wp_enqueue_style(
        'font-awesome-6',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        [],
        '6.5.1'
    );

    // Parent theme style
    wp_enqueue_style( 'pixelpiernyc-parent', get_template_directory_uri() . '/style.css' );

    // Child theme editorial stylesheet
    wp_enqueue_style(
        'gdas-editorial-style',
        get_stylesheet_directory_uri() . '/assets/css/gdas-editorial.css',
        ['pixelpiernyc-parent', 'gdas-google-fonts', 'font-awesome-6'],
        filemtime( get_stylesheet_directory() . '/assets/css/gdas-editorial.css' )
    );

    // Child theme JS
    wp_enqueue_script(
        'gdas-main-script',
        get_stylesheet_directory_uri() . '/assets/js/gdas-main.js',
        ['jquery'],
        filemtime( get_stylesheet_directory() . '/assets/js/gdas-main.js' ),
        true
    );

    wp_localize_script( 'gdas-main-script', 'gdasData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'gdas_pitch_nonce' ),
    ] );
}

// Register Nav Menus
add_action( 'after_setup_theme', 'gdas_theme_setup' );
function gdas_theme_setup() {
    register_nav_menus([
        'gdas_primary' => __( 'G Das Primary Menu', 'pixelpiernyc-child' ),
        'gdas_footer'  => __( 'G Das Footer Menu', 'pixelpiernyc-child' ),
    ]);
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
}

// Custom Post Type for Founder Pitches
add_action( 'init', 'gdas_register_pitch_cpt' );
function gdas_register_pitch_cpt() {
    register_post_type( 'gdas_pitch', [
        'labels' => [
            'name'               => 'Founder Pitches',
            'singular_name'      => 'Founder Pitch',
            'menu_name'          => 'Pitches',
            'add_new'            => 'Add New Pitch',
            'add_new_item'       => 'Add New Pitch',
            'edit_item'          => 'View Pitch',
            'all_items'          => 'All Pitches',
        ],
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => ['title', 'editor', 'custom-fields'],
        'menu_icon'          => 'dashicons-briefcase',
    ]);
}

// AJAX Handler for Pitch Submissions
add_action( 'wp_ajax_gdas_submit_pitch', 'gdas_handle_pitch_submission' );
add_action( 'wp_ajax_nopriv_gdas_submit_pitch', 'gdas_handle_pitch_submission' );
function gdas_handle_pitch_submission() {
    check_ajax_referer( 'gdas_pitch_nonce', 'nonce' );

    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $company = sanitize_text_field( $_POST['company'] ?? '' );
    $website = esc_url_raw( $_POST['website'] ?? '' );
    $sector  = sanitize_text_field( $_POST['sector'] ?? '' );
    $stage   = sanitize_text_field( $_POST['stage'] ?? '' );
    $pitch   = sanitize_textarea_field( $_POST['pitch'] ?? '' );
    $deck    = esc_url_raw( $_POST['deck'] ?? '' );

    if ( empty( $name ) || empty( $email ) || empty( $company ) ) {
        wp_send_json_error( ['message' => 'Please fill in all required fields (Name, Email, Company).'] );
    }

    $content = "Founder: $name\nEmail: $email\nCompany: $company\nWebsite: $website\nSector: $sector\nStage: $stage\nDeck Link: $deck\n\n--- Pitch Details ---\n$pitch";

    $post_id = wp_insert_post([
        'post_type'    => 'gdas_pitch',
        'post_title'   => "$company — $name",
        'post_content' => $content,
        'post_status'  => 'publish',
    ]);

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( ['message' => 'Unable to save your submission at this time.'] );
    }

    update_post_meta( $post_id, '_gdas_founder_name', $name );
    update_post_meta( $post_id, '_gdas_founder_email', $email );
    update_post_meta( $post_id, '_gdas_company', $company );
    update_post_meta( $post_id, '_gdas_website', $website );
    update_post_meta( $post_id, '_gdas_sector', $sector );
    update_post_meta( $post_id, '_gdas_stage', $stage );
    update_post_meta( $post_id, '_gdas_deck', $deck );

    wp_send_json_success( [
        'message' => 'Thank you for introducing your company. Our team will review your submission and reach out if there is a mutual fit.'
    ] );
}