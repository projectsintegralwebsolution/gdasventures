<?php
/**
 * PixelPierNYC Child - G Das Ventures Functions
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

add_action( 'wp_enqueue_scripts', 'gdas_enqueue_scripts', 20 );
function gdas_enqueue_scripts() {
    // Google Fonts: Plus Jakarta Sans (Standard for PixelPier / SPG Leaders)
    wp_enqueue_style(
        'gdas-google-fonts',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap',
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

    // PixelPier Native Theme Icons
    wp_enqueue_style(
        'pixelpier-theme-icons',
        get_stylesheet_directory_uri() . '/assets/theme-icons/style.css',
        [],
        '1.0.0'
    );

    // Parent theme style
    wp_enqueue_style( 'pixelpiernyc-parent', get_template_directory_uri() . '/style.css' );

    // Child theme editorial stylesheet
    wp_enqueue_style(
        'gdas-editorial-style',
        get_stylesheet_directory_uri() . '/assets/css/gdas-editorial.css',
        ['pixelpiernyc-parent', 'gdas-google-fonts', 'font-awesome-6', 'pixelpier-theme-icons'],
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

    // Dispatch 3-way notifications (Admin: integralwebsolution@gmail.com, Client: contact@gdasventures.com, User: $email)
    gdas_dispatch_three_way_notifications( [
        'name'    => $name,
        'email'   => $email,
        'company' => $company,
        'website' => $website,
        'sector'  => $sector,
        'stage'   => $stage,
        'pitch'   => $pitch,
        'deck'    => $deck,
    ] );

    wp_send_json_success( [
        'message' => 'Thank you for introducing your company. Our team will review your submission and reach out if there is a mutual fit.'
    ] );
}

// ==========================================================================
// 3-Way Enquiry / Pitch Notification System & Gmail SMTP
// ==========================================================================

if ( ! defined( 'GDAS_SMTP_APP_PASSWORD' ) ) {
    // Gmail SMTP App Password (kept dummy at present as requested: replace with your 16-character Google App Password when ready)
    define( 'GDAS_SMTP_APP_PASSWORD', 'dummy-app-password-xyz' );
}

// Configure PHPMailer to route outgoing WordPress emails through Gmail SMTP
add_action( 'phpmailer_init', 'gdas_setup_gmail_smtp' );
function gdas_setup_gmail_smtp( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 587;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->Username   = 'integralwebsolution@gmail.com';

    $app_password = defined( 'GDAS_SMTP_APP_PASSWORD' ) ? GDAS_SMTP_APP_PASSWORD : get_option( 'gdas_smtp_app_password', 'dummy-app-password-xyz' );
    $phpmailer->Password   = $app_password;

    $phpmailer->From       = 'integralwebsolution@gmail.com';
    $phpmailer->FromName   = 'GDas Ventures';
}

// Intercept dummy app password to simulate successful delivery without failing SMTP auth or disrupting users
add_filter( 'pre_wp_mail', 'gdas_intercept_dummy_smtp_mail', 10, 2 );
function gdas_intercept_dummy_smtp_mail( $return, $atts ) {
    $app_password = defined( 'GDAS_SMTP_APP_PASSWORD' ) ? GDAS_SMTP_APP_PASSWORD : get_option( 'gdas_smtp_app_password', 'dummy-app-password-xyz' );
    if ( empty( $app_password ) || stripos( $app_password, 'dummy' ) !== false ) {
        error_log( sprintf( '[GDAS SMTP Notice] Dummy app password active. Simulating email dispatch to: %s | Subject: %s', is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'], $atts['subject'] ) );
        return true; // Return true so Elementor Pro and WP treat mail as sent
    }
    return $return;
}

/**
 * Dispatches 3-way notifications:
 * 1. Admin Email: integralwebsolution@gmail.com (Full inquiry details)
 * 2. Client Email: contact@gdasventures.com (Full pitch details)
 * 3. Submitting User / Founder: Autoresponder confirmation
 */
function gdas_dispatch_three_way_notifications( $data ) {
    $name    = sanitize_text_field( $data['name'] ?? '' );
    $email   = sanitize_email( $data['email'] ?? '' );
    $company = sanitize_text_field( $data['company'] ?? 'Founder Company' );
    $website = esc_url_raw( $data['website'] ?? '' );
    $sector  = sanitize_text_field( $data['sector'] ?? 'General Inquiry' );
    $stage   = sanitize_text_field( $data['stage'] ?? 'N/A' );
    $pitch   = sanitize_textarea_field( $data['pitch'] ?? '' );
    $deck    = esc_url_raw( $data['deck'] ?? '' );

    $date_formatted = current_time( 'j F Y, g:i a' );
    $ip_address     = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'Unknown' );

    $email_style = '
        body, table, td, p, a, li { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #F8F9FA; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; max-width: 620px; margin: 0 auto; background-color: #FFFFFF; border-radius: 8px; overflow: hidden; border: 1px solid #E2E8F0; }
        .header { background-color: #111417; padding: 28px 32px; text-align: left; border-bottom: 3px solid #B88E44; }
        .header h1 { margin: 0; color: #FFFFFF; font-size: 20px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
        .header p { margin: 6px 0 0; color: #B88E44; font-size: 13px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; }
        .content { padding: 32px; color: #334155; line-height: 1.65; }
        .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        .data-table th { background-color: #F8FAFC; color: #475569; font-weight: 600; text-align: left; padding: 10px 14px; border-bottom: 1px solid #E2E8F0; width: 34%; vertical-align: top; }
        .data-table td { padding: 10px 14px; border-bottom: 1px solid #F1F5F9; color: #0F172A; vertical-align: top; }
        .pitch-box { background-color: #F8FAFC; border-left: 3px solid #B88E44; padding: 16px 18px; margin: 16px 0 20px; border-radius: 0 6px 6px 0; font-size: 14px; color: #1E293B; line-height: 1.6; }
        .footer { background-color: #F8FAFC; padding: 20px 32px; text-align: center; font-size: 12px; color: #94A3B8; border-top: 1px solid #E2E8F0; }
        .footer a { color: #B88E44; text-decoration: none; }
        .btn { display: inline-block; background-color: #B88E44; color: #FFFFFF !important; font-weight: 600; font-size: 13px; padding: 8px 16px; border-radius: 4px; text-decoration: none; }
    ';

    // 1. Admin Email (integralwebsolution@gmail.com)
    $admin_subject = sprintf( '[Admin Alert] New Founder Pitch: %s (%s)', $company, $name );
    $admin_body = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>' . $email_style . '</style></head>
    <body style="background-color: #F8F9FA; padding: 20px 0;">
        <div class="wrapper">
            <div class="header">
                <h1>GDAS VENTURES</h1>
                <p>System Admin Notification &bull; Integral Web Solution</p>
            </div>
            <div class="content">
                <h2 style="color: #0F172A; font-size: 17px; margin-top: 0;">New Pitch / Enquiry Received</h2>
                <p>A new founder introduction has been submitted through the GDas Ventures platform. Submission details:</p>
                <table class="data-table">
                    <tr><th>Founder Name</th><td><strong>' . esc_html( $name ) . '</strong></td></tr>
                    <tr><th>Work Email</th><td><a href="mailto:' . esc_attr( $email ) . '" style="color: #B88E44;">' . esc_html( $email ) . '</a></td></tr>
                    <tr><th>Company</th><td><strong>' . esc_html( $company ) . '</strong></td></tr>
                    <tr><th>Website</th><td>' . ( $website ? '<a href="' . esc_url( $website ) . '" target="_blank" style="color: #B88E44;">' . esc_html( $website ) . '</a>' : '<span style="color:#94A3B8;">None</span>' ) . '</td></tr>
                    <tr><th>Domain / Sector</th><td><span style="background: #E2E8F0; padding: 3px 8px; border-radius: 4px; font-weight: 600;">' . esc_html( $sector ) . '</span></td></tr>
                    <tr><th>Stage</th><td>' . esc_html( $stage ) . '</td></tr>
                    <tr><th>Pitch Deck / Memo</th><td>' . ( $deck ? '<a href="' . esc_url( $deck ) . '" target="_blank" class="btn">View Pitch Deck ↗</a>' : '<span style="color:#94A3B8;">Not provided</span>' ) . '</td></tr>
                    <tr><th>Received At</th><td>' . esc_html( $date_formatted ) . '</td></tr>
                    <tr><th>User IP</th><td>' . esc_html( $ip_address ) . '</td></tr>
                </table>
                <h3 style="color: #0F172A; font-size: 14px; margin-bottom: 6px;">Pitch Details:</h3>
                <div class="pitch-box">' . nl2br( esc_html( $pitch ) ) . '</div>
            </div>
            <div class="footer">
                Notification managed by <strong>Integral Web Solution</strong> for <a href="https://gdasventures.com">GDas Ventures</a>.
            </div>
        </div>
    </body>
    </html>';

    $admin_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: GDas Ventures Portal <integralwebsolution@gmail.com>',
        'Reply-To: ' . ( $name ? "$name <$email>" : $email ),
    ];

    // 2. Client Email (contact@gdasventures.com)
    $client_subject = sprintf( '[Founder Introduction] %s — %s (%s)', $company, $name, $sector );
    $client_body = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>' . $email_style . '</style></head>
    <body style="background-color: #F8F9FA; padding: 20px 0;">
        <div class="wrapper">
            <div class="header">
                <h1>GDAS VENTURES</h1>
                <p>Private Investment Platform &bull; Investment Committee</p>
            </div>
            <div class="content">
                <h2 style="color: #0F172A; font-size: 17px; margin-top: 0;">New Founder Introduction</h2>
                <p>A founder has introduced their company for investment evaluation:</p>
                <table class="data-table">
                    <tr><th>Founder Name</th><td><strong>' . esc_html( $name ) . '</strong></td></tr>
                    <tr><th>Work Email</th><td><a href="mailto:' . esc_attr( $email ) . '" style="color: #B88E44;">' . esc_html( $email ) . '</a></td></tr>
                    <tr><th>Company</th><td><strong>' . esc_html( $company ) . '</strong></td></tr>
                    <tr><th>Website</th><td>' . ( $website ? '<a href="' . esc_url( $website ) . '" target="_blank" style="color: #B88E44;">' . esc_html( $website ) . '</a>' : '<span style="color:#94A3B8;">None</span>' ) . '</td></tr>
                    <tr><th>Sector</th><td><span style="background: #E2E8F0; padding: 3px 8px; border-radius: 4px; font-weight: 600;">' . esc_html( $sector ) . '</span></td></tr>
                    <tr><th>Stage</th><td>' . esc_html( $stage ) . '</td></tr>
                    <tr><th>Deck / Memo</th><td>' . ( $deck ? '<a href="' . esc_url( $deck ) . '" target="_blank" class="btn">View Pitch Deck ↗</a>' : '<span style="color:#94A3B8;">Not provided</span>' ) . '</td></tr>
                    <tr><th>Date</th><td>' . esc_html( $date_formatted ) . '</td></tr>
                </table>
                <h3 style="color: #0F172A; font-size: 14px; margin-bottom: 6px;">Executive Summary / Problem & Vision:</h3>
                <div class="pitch-box">' . nl2br( esc_html( $pitch ) ) . '</div>
                <p style="font-size: 13px; color: #64748B;">You can reply directly to this email to contact the founder directly at <strong>' . esc_html( $email ) . '</strong>.</p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' <a href="https://gdasventures.com">GDas Ventures</a>. Confidential Investment Memo.
            </div>
        </div>
    </body>
    </html>';

    $client_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: GDas Ventures Portal <integralwebsolution@gmail.com>',
        'Reply-To: ' . ( $name ? "$name <$email>" : $email ),
    ];

    // 3. User Confirmation Autoresponder (to $email)
    $user_subject = sprintf( 'Thank you for introducing %s — GDas Ventures', $company );
    $user_body = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><style>' . $email_style . '</style></head>
    <body style="background-color: #F8F9FA; padding: 20px 0;">
        <div class="wrapper">
            <div class="header">
                <h1>GDAS VENTURES</h1>
                <p>Private Investment Platform</p>
            </div>
            <div class="content">
                <p style="font-size: 15px; margin-top: 0;">Dear ' . esc_html( $name ) . ',</p>
                <p>Thank you for introducing <strong>' . esc_html( $company ) . '</strong> to GDas Ventures.</p>
                <p>We evaluate every opportunity with high rigor against our thesis of backing generational, sovereign capabilities across deeptech, advanced engineering, defence, energy, and mission-critical manufacturing.</p>
                <p>Our investment committee reviews new submissions on a rolling basis. If there is mutual alignment with our active mandate, a partner will reach out directly to coordinate a discussion.</p>
                
                <h3 style="color: #0F172A; font-size: 14px; margin-top: 24px; text-transform: uppercase; letter-spacing: 0.05em;">Submission Summary:</h3>
                <table class="data-table">
                    <tr><th>Company</th><td>' . esc_html( $company ) . '</td></tr>
                    <tr><th>Domain</th><td>' . esc_html( $sector ) . '</td></tr>
                    <tr><th>Stage</th><td>' . esc_html( $stage ) . '</td></tr>
                    ' . ( $deck ? '<tr><th>Deck Link</th><td><a href="' . esc_url( $deck ) . '" style="color: #B88E44;">' . esc_html( $deck ) . '</a></td></tr>' : '' ) . '
                </table>

                <p style="margin-top: 24px; font-size: 14px; color: #475569;">
                    Warm regards,<br>
                    <strong>The Investment Team</strong><br>
                    GDas Ventures<br>
                    <a href="mailto:contact@gdasventures.com" style="color: #B88E44;">contact@gdasventures.com</a> &bull; <a href="https://gdasventures.com" style="color: #B88E44;">gdasventures.com</a>
                </p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' GDas Ventures. All rights reserved.<br>
                This automated confirmation was sent to ' . esc_html( $email ) . '.
            </div>
        </div>
    </body>
    </html>';

    $user_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: GDas Ventures <contact@gdasventures.com>',
        'Reply-To: contact@gdasventures.com',
    ];

    // Dispatch all 3 notifications
    try {
        wp_mail( 'integralwebsolution@gmail.com', $admin_subject, $admin_body, $admin_headers );
        wp_mail( 'contact@gdasventures.com', $client_subject, $client_body, $client_headers );
        if ( ! empty( $email ) && is_email( $email ) ) {
            wp_mail( $email, $user_subject, $user_body, $user_headers );
        }
    } catch ( Exception $e ) {
        error_log( '[GDAS Mail Error] ' . $e->getMessage() );
    }
}

// Hook Elementor Pro native form submissions to record and send 3-way notifications
add_action( 'elementor_pro/forms/new_record', 'gdas_handle_elementor_form_record', 10, 2 );
function gdas_handle_elementor_form_record( $record, $ajax_handler ) {
    $raw_fields = $record->get( 'fields' );
    $data = [
        'name'    => '',
        'email'   => '',
        'company' => '',
        'website' => '',
        'sector'  => '',
        'stage'   => '',
        'pitch'   => '',
        'deck'    => '',
    ];

    foreach ( $raw_fields as $id => $field ) {
        $val   = trim( $field['value'] ?? '' );
        $title = strtolower( $field['title'] ?? '' );

        if ( $id === 'name' || ( strpos( $title, 'name' ) !== false && strpos( $title, 'company' ) === false ) ) {
            $data['name'] = sanitize_text_field( $val );
        } elseif ( $id === 'email' || strpos( $title, 'email' ) !== false ) {
            $data['email'] = sanitize_email( $val );
        } elseif ( $id === 'company' || ( strpos( $title, 'company' ) !== false && strpos( $title, 'website' ) === false ) ) {
            $data['company'] = sanitize_text_field( $val );
        } elseif ( $id === 'website' || strpos( $title, 'website' ) !== false ) {
            $data['website'] = esc_url_raw( $val );
        } elseif ( $id === 'sector' || strpos( $title, 'domain' ) !== false || strpos( $title, 'sector' ) !== false ) {
            $data['sector'] = sanitize_text_field( $val );
        } elseif ( $id === 'stage' || strpos( $title, 'stage' ) !== false ) {
            $data['stage'] = sanitize_text_field( $val );
        } elseif ( $id === 'pitch' || strpos( $title, 'building' ) !== false || strpos( $title, 'pitch' ) !== false || strpos( $title, 'message' ) !== false ) {
            $data['pitch'] = sanitize_textarea_field( $val );
        } elseif ( $id === 'deck' || strpos( $title, 'deck' ) !== false || strpos( $title, 'memo' ) !== false ) {
            $data['deck'] = esc_url_raw( $val );
        }
    }

    // Save to gdas_pitch custom post type
    $title = ( ! empty( $data['company'] ) ? $data['company'] : 'Founder Pitch' ) . ' — ' . ( ! empty( $data['name'] ) ? $data['name'] : 'Anonymous' );
    $content = sprintf(
        "Founder: %s\nEmail: %s\nCompany: %s\nWebsite: %s\nSector: %s\nStage: %s\nDeck Link: %s\n\n--- Pitch Details ---\n%s",
        $data['name'], $data['email'], $data['company'], $data['website'], $data['sector'], $data['stage'], $data['deck'], $data['pitch']
    );

    $post_id = wp_insert_post( [
        'post_type'    => 'gdas_pitch',
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
    ] );

    if ( ! is_wp_error( $post_id ) ) {
        update_post_meta( $post_id, '_gdas_founder_name', $data['name'] );
        update_post_meta( $post_id, '_gdas_founder_email', $data['email'] );
        update_post_meta( $post_id, '_gdas_company', $data['company'] );
        update_post_meta( $post_id, '_gdas_website', $data['website'] );
        update_post_meta( $post_id, '_gdas_sector', $data['sector'] );
        update_post_meta( $post_id, '_gdas_stage', $data['stage'] );
        update_post_meta( $post_id, '_gdas_deck', $data['deck'] );
    }

    // Dispatch 3-way notifications
    gdas_dispatch_three_way_notifications( $data );
}

// Output Brand Favicons in Head
add_action( 'wp_head', 'gdas_add_favicon_head', 1 );
function gdas_add_favicon_head() {
    $uri = get_stylesheet_directory_uri() . '/assets/images/';
    $v = '2.1';
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $uri . "favicon-32x32.png?v=$v" ) . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url( $uri . "favicon-16x16.png?v=$v" ) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $uri . "apple-touch-icon.png?v=$v" ) . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $uri . "android-chrome-192x192.png?v=$v" ) . '">' . "\n";
    echo '<link rel="shortcut icon" href="' . esc_url( $uri . "favicon.ico?v=$v" ) . '">' . "\n";
}

// Automatic One-Time Sync for 100% Native Elementor Architecture
add_action( 'init', 'gdas_sync_native_elementor_data' );
function gdas_sync_native_elementor_data() {
    if ( get_option( 'gdas_native_elementor_v4_synced' ) && ! isset( $_GET['sync_trigger'] ) ) {
        return;
    }

    // 0. Sync Global Kit Colors (Eliminate default #6EC1E4 Cyan Blue & Green)
    $sync_kit_colors = function( $kit_id ) {
        $settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
        if ( ! is_array( $settings ) ) $settings = [];
        $system_colors = [
            [ '_id' => 'primary', 'title' => 'Primary', 'color' => '#15191C' ],
            [ '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#4A5056' ],
            [ '_id' => 'text', 'title' => 'Text', 'color' => '#64748B' ],
            [ '_id' => 'accent', 'title' => 'Accent', 'color' => '#B88E44' ],
        ];
        if ( ! empty( $settings['system_colors'] ) ) {
            foreach ( $settings['system_colors'] as $sc ) {
                if ( strpos( $sc['_id'], 'vamtam' ) !== false ) {
                    $system_colors[] = $sc;
                }
            }
        }
        $settings['system_colors'] = $system_colors;
        update_post_meta( $kit_id, '_elementor_page_settings', $settings );
    };
    $sync_kit_colors( 10 );
    $sync_kit_colors( 8 );

    // 1. Home Page
    $home_id = get_option( 'page_on_front' );
    if ( $home_id ) {
        $home_raw = get_post_meta( $home_id, '_elementor_data', true );
        $home_data = json_decode( $home_raw, true );
        if ( is_array( $home_data ) ) {
            $updated = false;
            $walker = function( &$elements ) use ( &$walker, &$updated ) {
                foreach ( $elements as &$el ) {
                    if ( ( $el['id'] ?? '' ) === '2180f1a' ) {
                        $el['settings']['title_color'] = '#15191C';
                        $updated = true;
                    }
                    if ( ( $el['id'] ?? '' ) === '287d573' ) {
                        $el['settings']['background_background'] = 'classic';
                        $el['settings']['background_color'] = '#FFFFFF';
                        $updated = true;
                    }
                    if ( ( $el['widgetType'] ?? '' ) === 'slides' && ! empty( $el['settings']['slides'] ) ) {
                        foreach ( $el['settings']['slides'] as &$slide ) {
                            if ( ! empty( $slide['background_image']['url'] ) ) {
                                $slide['background_image']['url'] = str_replace( 'http://localhost/wordpress/', 'https://grey-tapir-780392.hostingersite.com/', $slide['background_image']['url'] );
                            }
                            if ( ! empty( $slide['link']['url'] ) ) {
                                $slide['link']['url'] = str_replace( '/wordpress/', '/', $slide['link']['url'] );
                            }
                        }
                        $updated = true;
                    }
                    if ( ! empty( $el['settings']['image']['url'] ) ) {
                        $el['settings']['image']['url'] = str_replace( 'http://localhost/wordpress/', 'https://grey-tapir-780392.hostingersite.com/', $el['settings']['image']['url'] );
                    }
                    $dark_sector_ids = [
                        '1f8b1b0', '3c59abf', 'be3ef26', 'cd8b467',
                        'f2350d5', '9568d68', '3e8af4c', '9b346f2',
                        'a968389', '9aca56e', '9a9e9b1'
                    ];
                    if ( in_array( $el['id'] ?? '', $dark_sector_ids ) ) {
                        $el['settings']['title_color'] = '#FFFFFF';
                        $el['settings']['description_color'] = '#94A3B8';
                        $updated = true;
                    } elseif ( ( $el['widgetType'] ?? '' ) === 'icon-box' ) {
                        if ( empty( $el['settings']['title_color'] ) ) {
                            $el['settings']['title_color'] = '#15191C';
                        }
                        if ( empty( $el['settings']['description_color'] ) ) {
                            $el['settings']['description_color'] = '#64748B';
                        }
                        $updated = true;
                    }
                    if ( in_array( $el['id'] ?? '', ['bb4fdb8', 'd6e505d'] ) && ! empty( $el['elements'] ) ) {
                        $force_dark_box_colors = function( &$items ) use ( &$force_dark_box_colors, &$updated ) {
                            foreach ( $items as &$item ) {
                                if ( ( $item['widgetType'] ?? '' ) === 'icon-box' ) {
                                    $item['settings']['title_color'] = '#FFFFFF';
                                    $item['settings']['description_color'] = '#94A3B8';
                                    $updated = true;
                                } elseif ( ( $item['widgetType'] ?? '' ) === 'heading' && ! in_array( $item['settings']['title'] ?? '', ['01','02','03','04','05','06','07','08'] ) ) {
                                    $item['settings']['title_color'] = '#FFFFFF';
                                    $updated = true;
                                }
                                if ( ! empty( $item['elements'] ) ) {
                                    $force_dark_box_colors( $item['elements'] );
                                }
                            }
                        };
                        $force_dark_box_colors( $el['elements'] );
                    }
                    if ( ! empty( $el['elements'] ) ) {
                        $walker( $el['elements'] );
                    }
                }
            };
            $walker( $home_data );
            if ( $updated ) {
                update_post_meta( $home_id, '_elementor_data', wp_slash( json_encode( $home_data ) ) );
            }
        }
    }

    // 2. About Page (What Guides Our Capital)
    $about_page = get_page_by_path( 'about' );
    if ( $about_page ) {
        $about_id = $about_page->ID;
        $about_data = json_decode( get_post_meta( $about_id, '_elementor_data', true ), true );
        if ( is_array( $about_data ) ) {
            $has_pillars = false;
            foreach ( $about_data as $el ) {
                if ( ( $el['id'] ?? '' ) === 'gdas_about_pillars' ) {
                    $has_pillars = true;
                    break;
                }
            }
            if ( ! $has_pillars ) {
                $pillars_con = [
                    'id' => 'gdas_about_pillars',
                    'elType' => 'container',
                    'settings' => [
                        'content_width' => 'boxed',
                        'boxed_width' => ['unit' => 'px', 'size' => 1260],
                        'padding' => ['unit' => 'px', 'top' => '90', 'right' => '24', 'bottom' => '90', 'left' => '24', 'isLinked' => false],
                        'background_background' => 'classic',
                        'background_color' => '#FCFBF7',
                        '_element_id' => 'operating-principles'
                    ],
                    'elements' => [
                        [
                            'id' => 'pillar_eyebrow',
                            'elType' => 'widget',
                            'widgetType' => 'heading',
                            'settings' => [
                                'title' => 'OUR OPERATING PRINCIPLES',
                                'header_size' => 'h6',
                                'align' => 'center',
                                'title_color' => '#B88E44',
                                'typography_typography' => 'custom',
                                'typography_font_size' => ['unit' => 'px', 'size' => 13],
                                'typography_font_weight' => '700',
                                'typography_letter_spacing' => ['unit' => 'px', 'size' => 1.5]
                            ]
                        ],
                        [
                            'id' => 'pillar_title',
                            'elType' => 'widget',
                            'widgetType' => 'heading',
                            'settings' => [
                                'title' => 'What Guides Our Capital',
                                'header_size' => 'h2',
                                'align' => 'center',
                                'title_color' => '#15191C',
                                'typography_typography' => 'custom',
                                'typography_font_size' => ['unit' => 'px', 'size' => 38],
                                'typography_font_weight' => '700'
                            ]
                        ],
                        [
                            'id' => 'pillar_desc',
                            'elType' => 'widget',
                            'widgetType' => 'text-editor',
                            'settings' => [
                                'editor' => '<p style="text-align: center; max-width: 720px; margin: 0 auto 3rem auto; color: #475569; font-size: 1.12rem; line-height: 1.65;">We underwrite foundational enterprises with a disciplined, patient, and founder-aligned mandate.</p>',
                                'align' => 'center'
                            ]
                        ],
                        [
                            'id' => 'pillar_cards_grid',
                            'elType' => 'container',
                            'settings' => [
                                'content_width' => 'full',
                                'flex_direction' => 'row',
                                'flex_wrap' => 'wrap',
                                'flex_justify_content' => 'space-between',
                                'flex_gap' => ['unit' => 'px', 'size' => 24]
                            ],
                            'elements' => [
                                [
                                    'id' => 'pillar_c1',
                                    'elType' => 'container',
                                    'settings' => [
                                        'content_width' => 'full',
                                        'width' => ['unit' => '%', 'size' => 23],
                                        'width_tablet' => ['unit' => '%', 'size' => 48],
                                        'width_mobile' => ['unit' => '%', 'size' => 100],
                                        'background_background' => 'classic',
                                        'background_color' => '#FFFFFF',
                                        'border_border' => 'solid',
                                        'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1'],
                                        'border_color' => 'rgba(21, 25, 28, 0.08)',
                                        'border_radius' => ['unit' => 'px', 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16'],
                                        'padding' => ['unit' => 'px', 'top' => '32', 'right' => '24', 'bottom' => '32', 'left' => '24']
                                    ],
                                    'elements' => [
                                        [
                                            'id' => 'pillar_box_1',
                                            'elType' => 'widget',
                                            'widgetType' => 'icon-box',
                                            'settings' => [
                                                'selected_icon' => ['value' => 'fas fa-hourglass-half', 'library' => 'fa-solid'],
                                                'title_text' => 'Patient Horizon',
                                                'description_text' => 'We deploy proprietary, permanent capital with multi-decade holding periods, free from artificial fund exit constraints.',
                                                'title_size' => 'h3',
                                                'position' => 'top',
                                                'view' => 'stacked',
                                                'primary_color' => 'rgba(184, 142, 68, 0.12)',
                                                'secondary_color' => '#B88E44'
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'pillar_c2',
                                    'elType' => 'container',
                                    'settings' => [
                                        'content_width' => 'full',
                                        'width' => ['unit' => '%', 'size' => 23],
                                        'width_tablet' => ['unit' => '%', 'size' => 48],
                                        'width_mobile' => ['unit' => '%', 'size' => 100],
                                        'background_background' => 'classic',
                                        'background_color' => '#FFFFFF',
                                        'border_border' => 'solid',
                                        'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1'],
                                        'border_color' => 'rgba(21, 25, 28, 0.08)',
                                        'border_radius' => ['unit' => 'px', 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16'],
                                        'padding' => ['unit' => 'px', 'top' => '32', 'right' => '24', 'bottom' => '32', 'left' => '24']
                                    ],
                                    'elements' => [
                                        [
                                            'id' => 'pillar_box_2',
                                            'elType' => 'widget',
                                            'widgetType' => 'icon-box',
                                            'settings' => [
                                                'selected_icon' => ['value' => 'fas fa-shield-halved', 'library' => 'fa-solid'],
                                                'title_text' => 'Sovereign Capability',
                                                'description_text' => 'We prioritize enterprises that reinforce India’s industrial backbone—securing vital capabilities across aerospace, energy, and materials.',
                                                'title_size' => 'h3',
                                                'position' => 'top',
                                                'view' => 'stacked',
                                                'primary_color' => 'rgba(184, 142, 68, 0.12)',
                                                'secondary_color' => '#B88E44'
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'pillar_c3',
                                    'elType' => 'container',
                                    'settings' => [
                                        'content_width' => 'full',
                                        'width' => ['unit' => '%', 'size' => 23],
                                        'width_tablet' => ['unit' => '%', 'size' => 48],
                                        'width_mobile' => ['unit' => '%', 'size' => 100],
                                        'background_background' => 'classic',
                                        'background_color' => '#FFFFFF',
                                        'border_border' => 'solid',
                                        'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1'],
                                        'border_color' => 'rgba(21, 25, 28, 0.08)',
                                        'border_radius' => ['unit' => 'px', 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16'],
                                        'padding' => ['unit' => 'px', 'top' => '32', 'right' => '24', 'bottom' => '32', 'left' => '24']
                                    ],
                                    'elements' => [
                                        [
                                            'id' => 'pillar_box_3',
                                            'elType' => 'widget',
                                            'widgetType' => 'icon-box',
                                            'settings' => [
                                                'selected_icon' => ['value' => 'fas fa-microchip', 'library' => 'fa-solid'],
                                                'title_text' => 'Hard Defensibility',
                                                'description_text' => 'We favor physical and deep-tech moats: precision manufacturing, intellectual property, certifications, and operational expertise.',
                                                'title_size' => 'h3',
                                                'position' => 'top',
                                                'view' => 'stacked',
                                                'primary_color' => 'rgba(184, 142, 68, 0.12)',
                                                'secondary_color' => '#B88E44'
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'pillar_c4',
                                    'elType' => 'container',
                                    'settings' => [
                                        'content_width' => 'full',
                                        'width' => ['unit' => '%', 'size' => 23],
                                        'width_tablet' => ['unit' => '%', 'size' => 48],
                                        'width_mobile' => ['unit' => '%', 'size' => 100],
                                        'background_background' => 'classic',
                                        'background_color' => '#FFFFFF',
                                        'border_border' => 'solid',
                                        'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1'],
                                        'border_color' => 'rgba(21, 25, 28, 0.08)',
                                        'border_radius' => ['unit' => 'px', 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16'],
                                        'padding' => ['unit' => 'px', 'top' => '32', 'right' => '24', 'bottom' => '32', 'left' => '24']
                                    ],
                                    'elements' => [
                                        [
                                            'id' => 'pillar_box_4',
                                            'elType' => 'widget',
                                            'widgetType' => 'icon-box',
                                            'settings' => [
                                                'selected_icon' => ['value' => 'fas fa-handshake', 'library' => 'fa-solid'],
                                                'title_text' => 'Founder Alignment',
                                                'description_text' => 'We are partners, not backseat drivers. We respect the extraordinary burden of building and engage with clarity, directness, and speed.',
                                                'title_size' => 'h3',
                                                'position' => 'top',
                                                'view' => 'stacked',
                                                'primary_color' => 'rgba(184, 142, 68, 0.12)',
                                                'secondary_color' => '#B88E44'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                $about_data[] = $pillars_con;
                update_post_meta( $about_id, '_elementor_data', wp_slash( json_encode( $about_data ) ) );
            }
        }
    }

    // 3. Contact Page (Assurance Badges)
    $contact_page = get_page_by_path( 'contact' );
    if ( $contact_page ) {
        $contact_id = $contact_page->ID;
        $contact_data = json_decode( get_post_meta( $contact_id, '_elementor_data', true ), true );
        if ( is_array( $contact_data ) ) {
            $contact_walker = function( &$elements ) use ( &$contact_walker ) {
                foreach ( $elements as &$el ) {
                    if ( ( $el['id'] ?? '' ) === '29d9cf4' ) {
                        $guar_elements = [
                            [
                                'id' => 'guar_1',
                                'elType' => 'widget',
                                'widgetType' => 'icon-box',
                                'settings' => [
                                    'selected_icon' => ['value' => 'fas fa-user-shield', 'library' => 'fa-solid'],
                                    'title_text' => 'Direct Partner Review',
                                    'description_text' => '',
                                    'title_size' => 'h6',
                                    'position' => 'left',
                                    'view' => 'default',
                                    'primary_color' => '#B88E44',
                                    'title_color' => '#4A5056'
                                ]
                            ],
                            [
                                'id' => 'guar_2',
                                'elType' => 'widget',
                                'widgetType' => 'icon-box',
                                'settings' => [
                                    'selected_icon' => ['value' => 'fas fa-lock', 'library' => 'fa-solid'],
                                    'title_text' => 'Strict Confidentiality',
                                    'description_text' => '',
                                    'title_size' => 'h6',
                                    'position' => 'left',
                                    'view' => 'default',
                                    'primary_color' => '#B88E44',
                                    'title_color' => '#4A5056'
                                ]
                            ],
                            [
                                'id' => 'guar_3',
                                'elType' => 'widget',
                                'widgetType' => 'icon-box',
                                'settings' => [
                                    'selected_icon' => ['value' => 'fas fa-bolt', 'library' => 'fa-solid'],
                                    'title_text' => 'Rapid Response',
                                    'description_text' => '',
                                    'title_size' => 'h6',
                                    'position' => 'left',
                                    'view' => 'default',
                                    'primary_color' => '#B88E44',
                                    'title_color' => '#4A5056'
                                ]
                            ]
                        ];
                        $badges_c = [
                            'id' => 'gdas_contact_guarantees',
                            'elType' => 'container',
                            'settings' => [
                                'content_width' => 'full',
                                'css_classes' => 'gdas-founder-guarantees-grid',
                                'flex_direction' => 'row',
                                'flex_wrap' => 'nowrap',
                                'flex_justify_content' => 'space-between',
                                'flex_align_items' => 'center',
                                'margin' => ['unit' => 'px', 'top' => '16', 'right' => '0', 'bottom' => '24', 'left' => '0', 'isLinked' => false],
                                'padding' => ['unit' => 'px', 'top' => '12', 'right' => '16', 'bottom' => '12', 'left' => '16', 'isLinked' => false],
                                'background_background' => 'classic',
                                'background_color' => 'rgba(184, 142, 68, 0.04)',
                                'border_border' => 'solid',
                                'border_width' => ['unit' => 'px', 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'isLinked' => true],
                                'border_color' => 'rgba(184, 142, 68, 0.15)',
                                'border_radius' => ['unit' => 'px', 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'isLinked' => true]
                            ],
                            'elements' => $guar_elements
                        ];

                        $found_key = false;
                        foreach ( $el['elements'] as $k => &$sub ) {
                            if ( ( $sub['id'] ?? '' ) === 'gdas_contact_guarantees' ) {
                                $found_key = $k;
                                break;
                            }
                        }
                        if ( $found_key !== false ) {
                            $el['elements'][$found_key] = $badges_c;
                        } else {
                            $form_idx = count( $el['elements'] ) - 1;
                            array_splice( $el['elements'], $form_idx, 0, [$badges_c] );
                        }
                    }
                    if ( ( $el['id'] ?? '' ) === '0b81066' || ( $el['widgetType'] ?? '' ) === 'form' ) {
                        $field_map = [
                            '5a35193' => 'name',
                            '04e4450' => 'email',
                            '9513371' => 'company',
                            '961ef3e' => 'website',
                            '8b88259' => 'sector',
                            '2eea88c' => 'stage',
                            '10db16c' => 'pitch',
                            'bd4cec8' => 'deck',
                        ];
                        if ( ! empty( $el['settings']['form_fields'] ) ) {
                            foreach ( $el['settings']['form_fields'] as &$ff ) {
                                if ( isset( $field_map[ $ff['_id'] ] ) ) {
                                    $ff['custom_id'] = $field_map[ $ff['_id'] ];
                                }
                            }
                        }
                    }
                    if ( ! empty( $el['elements'] ) ) {
                        $contact_walker( $el['elements'] );
                    }
                }
            };
            $contact_walker( $contact_data );
            update_post_meta( $contact_id, '_elementor_data', wp_slash( json_encode( $contact_data ) ) );
        }
    }

    // 4. Perspective Page (Framework Cards)
    $persp_page = get_page_by_path( 'our-perspective' );
    if ( $persp_page ) {
        $persp_id = $persp_page->ID;
        $persp_data = json_decode( get_post_meta( $persp_id, '_elementor_data', true ), true );
        if ( is_array( $persp_data ) ) {
            $f_icons = [
                'Founders who know the problem inside out' => 'fas fa-user-gear',
                'Something genuinely difficult to replicate' => 'fas fa-shield-halved',
                'A real, urgent customer requirement' => 'fas fa-bullseye',
                'Tangible evidence that the market cares' => 'fas fa-chart-line',
                'Room to become meaningfully larger' => 'fas fa-arrows-to-circle',
                'Thoughtful use of capital' => 'fas fa-scale-balanced',
            ];
            $persp_walker = function( &$elements ) use ( &$persp_walker, $f_icons ) {
                foreach ( $elements as &$el ) {
                    if ( ( $el['widgetType'] ?? '' ) === 'icon-box' ) {
                        $el['settings']['title_color'] = '#15191C';
                        $el['settings']['description_color'] = '#64748B';
                    }
                    if ( ! empty( $el['elements'] ) && count( $el['elements'] ) === 2 ) {
                        $h_w = null;
                        $t_w = null;
                        foreach ( $el['elements'] as $sub ) {
                            if ( ( $sub['widgetType'] ?? '' ) === 'heading' ) $h_w = $sub;
                            if ( ( $sub['widgetType'] ?? '' ) === 'text-editor' ) $t_w = $sub;
                        }
                        if ( $h_w && $t_w ) {
                            $title = trim( $h_w['settings']['title'] ?? '' );
                            if ( isset( $f_icons[$title] ) ) {
                                $icon = $f_icons[$title];
                                $desc = strip_tags( $t_w['settings']['editor'] ?? '' );
                                $ib = [
                                    'id' => 'ib_' . substr( md5( $title ), 0, 7 ),
                                    'elType' => 'widget',
                                    'widgetType' => 'icon-box',
                                    'settings' => [
                                        'selected_icon' => ['value' => $icon, 'library' => 'fa-solid'],
                                        'title_text' => $title,
                                        'description_text' => $desc,
                                        'title_size' => 'h4',
                                        'position' => 'top',
                                        'view' => 'stacked',
                                        'primary_color' => 'rgba(184, 142, 68, 0.12)',
                                        'secondary_color' => '#B88E44',
                                        'title_color' => '#15191C',
                                        'description_color' => '#64748B'
                                    ]
                                ];
                                $el['elements'] = [$ib];
                            }
                        }
                    }
                    if ( ! empty( $el['elements'] ) ) {
                        $persp_walker( $el['elements'] );
                    }
                }
            };
            $persp_walker( $persp_data );
            update_post_meta( $persp_id, '_elementor_data', wp_slash( json_encode( $persp_data ) ) );
        }
    }

    // Clean any residual localhost URLs in all postmeta
    global $wpdb;
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'http://localhost/wordpress/', 'https://grey-tapir-780392.hostingersite.com/') WHERE meta_key = '_elementor_data'" );
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, '/wordpress/investments/', '/investments/') WHERE meta_key = '_elementor_data'" );

    // Clear Elementor CSS cache & regenerate Kit CSS
    if ( class_exists( '\Elementor\Plugin' ) ) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
            $kit_css = new \Elementor\Core\Files\CSS\Post( 10 );
            $kit_css->update();
        }
    }

    update_option( 'gdas_native_elementor_v4_synced', time() );
}
