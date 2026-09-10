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
    $name     = sanitize_text_field( $data['name'] ?? '' );
    $email    = sanitize_email( $data['email'] ?? '' );
    $company  = sanitize_text_field( $data['company'] ?? 'Founder Company' );
    $website  = esc_url_raw( $data['website'] ?? '' );
    $linkedin = esc_url_raw( $data['linkedin'] ?? '' );
    $sector   = sanitize_text_field( $data['sector'] ?? 'General Inquiry' );
    $stage    = sanitize_text_field( $data['stage'] ?? 'N/A' );
    $pitch    = sanitize_textarea_field( $data['pitch'] ?? '' );
    $deck     = esc_url_raw( $data['deck'] ?? '' );

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
                    ' . ( $linkedin ? '<tr><th>LinkedIn Profile</th><td><a href="' . esc_url( $linkedin ) . '" target="_blank" style="color: #B88E44;">' . esc_html( $linkedin ) . '</a></td></tr>' : '' ) . '
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
                <p>GDas Ventures &bull; Investment Committee</p>
            </div>
            <div class="content">
                <h2 style="color: #0F172A; font-size: 17px; margin-top: 0;">New Founder Introduction</h2>
                <p>A founder has introduced their company for investment evaluation:</p>
                <table class="data-table">
                    <tr><th>Founder Name</th><td><strong>' . esc_html( $name ) . '</strong></td></tr>
                    <tr><th>Work Email</th><td><a href="mailto:' . esc_attr( $email ) . '" style="color: #B88E44;">' . esc_html( $email ) . '</a></td></tr>
                    <tr><th>Company</th><td><strong>' . esc_html( $company ) . '</strong></td></tr>
                    <tr><th>Website</th><td>' . ( $website ? '<a href="' . esc_url( $website ) . '" target="_blank" style="color: #B88E44;">' . esc_html( $website ) . '</a>' : '<span style="color:#94A3B8;">None</span>' ) . '</td></tr>
                    ' . ( $linkedin ? '<tr><th>LinkedIn Profile</th><td><a href="' . esc_url( $linkedin ) . '" target="_blank" style="color: #B88E44;">' . esc_html( $linkedin ) . '</a></td></tr>' : '' ) . '
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
                <p>GDas Ventures</p>
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
                    ' . ( $linkedin ? '<tr><th>LinkedIn</th><td><a href="' . esc_url( $linkedin ) . '" style="color: #B88E44;">' . esc_html( $linkedin ) . '</a></td></tr>' : '' ) . '
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
        'name'     => '',
        'email'    => '',
        'company'  => '',
        'website'  => '',
        'linkedin' => '',
        'sector'   => '',
        'stage'    => '',
        'pitch'    => '',
        'deck'     => '',
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
        } elseif ( $id === 'linkedin' || strpos( $title, 'linkedin' ) !== false ) {
            $data['linkedin'] = esc_url_raw( $val );
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
        "Founder: %s\nEmail: %s\nCompany: %s\nWebsite: %s\nLinkedIn: %s\nSector: %s\nStage: %s\nDeck Link: %s\n\n--- Pitch Details ---\n%s",
        $data['name'], $data['email'], $data['company'], $data['website'], $data['linkedin'], $data['sector'], $data['stage'], $data['deck'], $data['pitch']
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
        update_post_meta( $post_id, '_gdas_linkedin', $data['linkedin'] );
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

// Automatic One-Time Sync for 100% Native Elementor Architecture (v5 Refinements)
add_action( 'init', 'gdas_sync_native_elementor_data' );
function gdas_sync_native_elementor_data() {
    if ( get_option( 'gdas_native_elementor_v5_synced' ) && ! isset( $_GET['sync_trigger'] ) ) {
        return;
    }

    $logo_base = get_stylesheet_directory_uri() . '/assets/images/portfolio-logos/';

    // 0. Sync Global Kit Colors (Obsidian and Gold)
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

    // 1. Home Page Refactor
    $home_id = get_option( 'page_on_front' );
    if ( ! $home_id ) {
        $home_p = get_page_by_path( 'home' );
        if ( $home_p ) $home_id = $home_p->ID;
    }
    if ( $home_id ) {
        $home_raw = get_post_meta( $home_id, '_elementor_data', true );
        $home_data = json_decode( $home_raw, true );
        if ( is_array( $home_data ) ) {
            // A. Remove PORTFOLIO, OUR UNDERWRITING, and FAQ sections
            $filtered_home = [];
            foreach ( $home_data as $sec ) {
                $sec_id = $sec['id'] ?? '';
                $sec_json = json_encode( $sec );

                // Check for Portfolio section
                if ( $sec_id === 'e5eda74' || $sec_id === '27a8a01' || stripos( $sec_json, 'Companies We Have Backed' ) !== false ) {
                    continue; // Eradicate from homepage
                }
                // Check for Underwriting section
                if ( $sec_id === 'ad65a78' || $sec_id === '0e9134f' || stripos( $sec_json, 'OUR UNDERWRITING' ) !== false || stripos( $sec_json, 'What Earns Our Conviction' ) !== false ) {
                    continue; // Eradicate from homepage
                }
                // Check for FAQ section
                if ( $sec_id === '58cbe85' || $sec_id === '6c3497b' || stripos( $sec_json, 'Frequently Asked Questions' ) !== false || stripos( $sec_json, 'accordion' ) !== false ) {
                    continue; // Eradicate from homepage
                }

                $filtered_home[] = $sec;
            }
            $home_data = $filtered_home;

            // B. Refactor Hero, Investment Focus (4 cards), and Commitment CTA
            $home_walker = function( &$elements ) use ( &$home_walker ) {
                foreach ( $elements as $k => &$el ) {
                    // Hero Slider: Keep ONLY Slide 1, remove image, solid dark #111417 background, disable autoplay
                    if ( ( $el['widgetType'] ?? '' ) === 'slides' ) {
                        if ( ! empty( $el['settings']['slides'] ) ) {
                            $slide1 = $el['settings']['slides'][0];
                            $slide1['background_image'] = [ 'url' => '', 'id' => '' ];
                            $slide1['background_color'] = '#111417';
                            $slide1['heading'] = "Backing the founders building India's industrial backbone.";
                            $slide1['description'] = "We invest our own patient capital into ambitious Indian companies solving technically hard problems across aerospace, heavy engineering, nuclear energy, and space technology.";
                            $slide1['button_text'] = "Explore Our Investments ↗";
                            $slide1['link'] = [ 'url' => '/investments/', 'is_external' => '', 'nofollow' => '', 'custom_attributes' => '' ];
                            $el['settings']['slides'] = [ $slide1 ];
                        }
                        $el['settings']['autoplay'] = 'no';
                        $el['settings']['pause_on_hover'] = 'no';
                        $el['settings']['arrows'] = 'no';
                        $el['settings']['dots'] = 'no';
                    }

                    // 2-Column Hero layout (if container hero exists): center and use solid #111417
                    if ( ( $el['id'] ?? '' ) === 'f76fe83' ) {
                        $el['settings']['background_color'] = '#111417';
                    }
                    if ( ( $el['id'] ?? '' ) === 'b21b600' ) {
                        // Remove side image container from hero
                        unset( $elements[$k] );
                        continue;
                    }
                    if ( ( $el['id'] ?? '' ) === '88de49c' ) {
                        $el['settings']['width'] = [ 'unit' => '%', 'size' => 100 ];
                        $el['settings']['max_width'] = [ 'unit' => 'px', 'size' => 880 ];
                    }

                    // Investment Focus Subtitle
                    if ( ( $el['id'] ?? '' ) === 'f4b4bd8' || ( ( $el['widgetType'] ?? '' ) === 'text-editor' && stripos( json_encode( $el ), 'deeptech' ) !== false && stripos( json_encode( $el ), 'defence' ) !== false ) ) {
                        $el['settings']['editor'] = '<p>We invest across four interconnected domains that define India’s sovereign future.</p>';
                    }
                    if ( ( $el['id'] ?? '' ) === '267b8e8' || ( $el['id'] ?? '' ) === '2180f1a' ) {
                        $el['settings']['title_color'] = '#15191C';
                    }

                    // Investment Focus: Exactly 4 Cards (Defence, Space, Advanced Manufacturing, Deeptech)
                    if ( ( $el['id'] ?? '' ) === 'd6e505d' && ! empty( $el['elements'] ) ) {
                        $cards_config = [
                            [ 'num' => '01', 'title' => 'Defence' ],
                            [ 'num' => '02', 'title' => 'Space' ],
                            [ 'num' => '03', 'title' => 'Advanced Manufacturing' ],
                            [ 'num' => '04', 'title' => 'Deeptech' ],
                        ];

                        $new_cards = [];
                        foreach ( $cards_config as $cidx => $cfg ) {
                            $card_el = $el['elements'][$cidx] ?? null;
                            if ( ! $card_el ) {
                                $card_el = [
                                    'id'       => 'foc_c_' . ( $cidx + 1 ),
                                    'elType'   => 'container',
                                    'settings' => [],
                                    'elements' => [],
                                    'isInner'  => true,
                                ];
                            }
                            $card_el['settings']['width'] = [ 'unit' => '%', 'size' => 23.5 ];
                            $card_el['settings']['width_tablet'] = [ 'unit' => '%', 'size' => 48 ];
                            $card_el['settings']['width_mobile'] = [ 'unit' => '%', 'size' => 100 ];
                            $card_el['settings']['background_background'] = 'classic';
                            $card_el['settings']['background_color'] = '#16191C';
                            $card_el['settings']['border_border'] = 'solid';
                            $card_el['settings']['border_width'] = [ 'unit' => 'px', 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'isLinked' => true ];
                            $card_el['settings']['border_color'] = 'rgba(255,255,255,0.12)';
                            $card_el['settings']['border_radius'] = [ 'unit' => 'px', 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'isLinked' => true ];
                            $card_el['settings']['padding'] = [ 'unit' => 'px', 'top' => 28, 'right' => 24, 'bottom' => 28, 'left' => 24, 'isLinked' => true ];

                            // Only keep Number heading and Domain Title heading; strip description text-editor
                            $card_el['elements'] = [
                                [
                                    'id'         => 'foc_num_' . ( $cidx + 1 ),
                                    'elType'     => 'widget',
                                    'widgetType' => 'heading',
                                    'settings'   => [
                                        'title'                 => $cfg['num'],
                                        'header_size'           => 'h6',
                                        'align'                 => 'left',
                                        'title_color'           => '#B88E44',
                                        'typography_typography' => 'custom',
                                        'typography_font_size'  => [ 'unit' => 'px', 'size' => 13 ],
                                        'typography_font_weight'=> '700',
                                        '_margin'               => [ 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 8, 'left' => 0, 'isLinked' => false ],
                                    ],
                                ],
                                [
                                    'id'         => 'foc_title_' . ( $cidx + 1 ),
                                    'elType'     => 'widget',
                                    'widgetType' => 'heading',
                                    'settings'   => [
                                        'title'                 => $cfg['title'],
                                        'header_size'           => 'h4',
                                        'align'                 => 'left',
                                        'title_color'           => '#FFFFFF',
                                        'typography_typography' => 'custom',
                                        'typography_font_size'  => [ 'unit' => 'px', 'size' => 20 ],
                                        'typography_font_weight'=> '700',
                                        '_margin'               => [ 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0, 'isLinked' => false ],
                                    ],
                                ],
                            ];
                            $new_cards[] = $card_el;
                        }
                        $el['elements'] = $new_cards;
                        $el['settings']['flex_direction'] = 'row';
                        $el['settings']['flex_wrap'] = 'wrap';
                        $el['settings']['flex_justify_content'] = 'space-between';
                    }

                    // Remove 2nd row of focus cards
                    if ( ( $el['id'] ?? '' ) === '44b62ec' ) {
                        unset( $elements[$k] );
                        continue;
                    }

                    // Our Commitment CTA Button
                    if ( ( $el['id'] ?? '' ) === '0d01931' || ( $el['id'] ?? '' ) === '56bb3f4' || ( ( $el['widgetType'] ?? '' ) === 'button' && stripos( json_encode( $el ), 'contact' ) !== false ) ) {
                        $el['settings']['text'] = 'Pitch to Us';
                        $el['settings']['link'] = [ 'url' => '/contact/', 'is_external' => '', 'nofollow' => '', 'custom_attributes' => '' ];
                    }

                    if ( ! empty( $el['elements'] ) ) {
                        $home_walker( $el['elements'] );
                    }
                }
                $elements = array_values( $elements );
            };
            $home_walker( $home_data );
            update_post_meta( $home_id, '_elementor_data', wp_slash( json_encode( $home_data ) ) );
        }
    }

    // 2. Investments Page Refactor (Replace photos with logos & shorten descriptions)
    $inv_page = get_page_by_path( 'investments' );
    if ( $inv_page ) {
        $inv_id = $inv_page->ID;
        $inv_data = json_decode( get_post_meta( $inv_id, '_elementor_data', true ), true );
        if ( is_array( $inv_data ) ) {
            $inv_walker = function( &$elements ) use ( &$inv_walker, $logo_base ) {
                foreach ( $elements as &$el ) {
                    // Kineco Logo & Description
                    if ( ( $el['id'] ?? '' ) === '180d998' || ( ( $el['widgetType'] ?? '' ) === 'image' && stripos( json_encode( $el ), 'kineco' ) !== false ) ) {
                        $el['settings']['image'] = [ 'url' => $logo_base . 'kineco-logo.png', 'id' => '' ];
                        $el['settings']['css_classes'] = 'gdas-portfolio-logo-img';
                    }
                    if ( ( $el['id'] ?? '' ) === '13e9641' ) {
                        $el['settings']['editor'] = '<p>Manufacturer of advanced composite structures for aerospace, defence, and high-speed rail systems, including mission-critical components for India’s space program.</p>';
                    }

                    // CORE Energy Logo & Description
                    if ( ( $el['id'] ?? '' ) === '48dbfe8' || ( ( $el['widgetType'] ?? '' ) === 'image' && stripos( json_encode( $el ), 'core' ) !== false ) ) {
                        $el['settings']['image'] = [ 'url' => $logo_base . 'core-logo.png', 'id' => '' ];
                        $el['settings']['css_classes'] = 'gdas-portfolio-logo-img';
                    }
                    if ( ( $el['id'] ?? '' ) === '7b6069e' ) {
                        $el['settings']['editor'] = '<p>Engineering and manufacturing partner supplying critical equipment, reactor packages, and custom solutions to India’s civilian nuclear and defense power programs.</p>';
                    }

                    // AirLife Gases Logo & Description
                    if ( ( $el['id'] ?? '' ) === '2539a59' || ( ( $el['widgetType'] ?? '' ) === 'image' && stripos( json_encode( $el ), 'airlife' ) !== false ) ) {
                        $el['settings']['image'] = [ 'url' => $logo_base . 'airlife-logo.svg', 'id' => '' ];
                        $el['settings']['css_classes'] = 'gdas-portfolio-logo-img';
                    }
                    if ( ( $el['id'] ?? '' ) === '4806815' ) {
                        $el['settings']['editor'] = '<p>Producer and distributor of ultra-high purity industrial and specialty gases powering semiconductor manufacturing, medical diagnostics (MRI), and deeptech research.</p>';
                    }

                    // Agnikul Cosmos Logo & Description
                    if ( ( $el['id'] ?? '' ) === '6839415' || ( ( $el['widgetType'] ?? '' ) === 'image' && stripos( json_encode( $el ), 'agnikul' ) !== false ) ) {
                        $el['settings']['image'] = [ 'url' => $logo_base . 'agnikul-logo.png', 'id' => '' ];
                        $el['settings']['css_classes'] = 'gdas-portfolio-logo-img';
                    }
                    if ( ( $el['id'] ?? '' ) === '6d1ddce' ) {
                        $el['settings']['editor'] = '<p>Pioneering private orbital launch vehicle developer building customizable, 3D-printed single-piece engines to deliver small satellites into low Earth orbit.</p>';
                    }

                    if ( ! empty( $el['elements'] ) ) {
                        $inv_walker( $el['elements'] );
                    }
                }
            };
            $inv_walker( $inv_data );
            update_post_meta( $inv_id, '_elementor_data', wp_slash( json_encode( $inv_data ) ) );
        }
    }

    // 3. Perspective Page Refactor (Remove side image, center editorial text layout)
    $persp_page = get_page_by_path( 'our-perspective' );
    if ( $persp_page ) {
        $persp_id = $persp_page->ID;
        $persp_data = json_decode( get_post_meta( $persp_id, '_elementor_data', true ), true );
        if ( is_array( $persp_data ) ) {
            $persp_walker = function( &$elements ) use ( &$persp_walker ) {
                foreach ( $elements as $k => &$el ) {
                    // Remove side image container 6b196da
                    if ( ( $el['id'] ?? '' ) === '6b196da' || ( ( $el['widgetType'] ?? '' ) === 'image' && stripos( json_encode( $el ), 'hero-builders' ) !== false ) ) {
                        unset( $elements[$k] );
                        continue;
                    }
                    // Reflow text container 1a80c7e into centered reading container
                    if ( ( $el['id'] ?? '' ) === '1a80c7e' ) {
                        $el['settings']['width'] = [ 'unit' => '%', 'size' => 100 ];
                        $el['settings']['max_width'] = [ 'unit' => 'px', 'size' => 860 ];
                        $el['settings']['css_classes'] = 'gdas-editorial-centered';
                    }
                    if ( ( $el['id'] ?? '' ) === '9277d51' ) {
                        $el['settings']['flex_direction'] = 'column';
                        $el['settings']['align_items'] = 'center';
                        $el['settings']['flex_justify_content'] = 'center';
                    }
                    if ( ! empty( $el['elements'] ) ) {
                        $persp_walker( $el['elements'] );
                    }
                }
                $elements = array_values( $elements );
            };
            $persp_walker( $persp_data );
            update_post_meta( $persp_id, '_elementor_data', wp_slash( json_encode( $persp_data ) ) );
        }
    }

    // 4. About Us Page Refactor (Remove side image, update headline/copy, delete operating principles)
    $about_page = get_page_by_path( 'about' );
    if ( $about_page ) {
        $about_id = $about_page->ID;
        $about_data = json_decode( get_post_meta( $about_id, '_elementor_data', true ), true );
        if ( is_array( $about_data ) ) {
            // Delete Section 2 (gdas_about_pillars / Operating Principles)
            $filtered_about = [];
            foreach ( $about_data as $sec ) {
                if ( ( $sec['id'] ?? '' ) === 'gdas_about_pillars' || stripos( json_encode( $sec ), 'OUR OPERATING PRINCIPLES' ) !== false || stripos( json_encode( $sec ), 'What Guides Our Capital' ) !== false ) {
                    continue;
                }
                $filtered_about[] = $sec;
            }
            $about_data = $filtered_about;

            $about_walker = function( &$elements ) use ( &$about_walker ) {
                foreach ( $elements as $k => &$el ) {
                    // Remove side image container edfbab8
                    if ( ( $el['id'] ?? '' ) === 'edfbab8' || ( ( $el['widgetType'] ?? '' ) === 'image' && stripos( json_encode( $el ), 'kineco' ) !== false ) ) {
                        unset( $elements[$k] );
                        continue;
                    }
                    // Reflow text container 219448a
                    if ( ( $el['id'] ?? '' ) === '219448a' ) {
                        $el['settings']['width'] = [ 'unit' => '%', 'size' => 100 ];
                        $el['settings']['max_width'] = [ 'unit' => 'px', 'size' => 860 ];
                        $el['settings']['css_classes'] = 'gdas-about-centered';
                    }
                    if ( ( $el['id'] ?? '' ) === '1bd1816' ) {
                        $el['settings']['flex_direction'] = 'column';
                        $el['settings']['align_items'] = 'center';
                        $el['settings']['flex_justify_content'] = 'center';
                    }
                    // Headline update: "Built for builders."
                    if ( ( $el['id'] ?? '' ) === '876cda5' ) {
                        $el['settings']['title'] = 'Built for builders.';
                    }
                    // Body text update (eradicate private investment platform)
                    if ( ( $el['id'] ?? '' ) === 'd1d830a' ) {
                        $el['settings']['editor'] = "<p>G Das Ventures was formed with an uncompromising belief: the most important companies of India’s future will build real, complex, physical capabilities.</p><p>We do not operate with the short-term pressures of fund lifecycles. We partner with exceptional founders who have deep conviction in their domain, deploying patient, long-horizon capital to help build industrial leaders that endure.</p>";
                    }

                    if ( ! empty( $el['elements'] ) ) {
                        $about_walker( $el['elements'] );
                    }
                }
                $elements = array_values( $elements );
            };
            $about_walker( $about_data );
            update_post_meta( $about_id, '_elementor_data', wp_slash( json_encode( $about_data ) ) );
        }
    }

    // 5. Contact Page Refactor (Remove Platform Model, add LinkedIn Profile, strip non-select placeholders)
    $contact_page = get_page_by_path( 'contact' );
    if ( $contact_page ) {
        $contact_id = $contact_page->ID;
        $contact_data = json_decode( get_post_meta( $contact_id, '_elementor_data', true ), true );
        if ( is_array( $contact_data ) ) {
            $contact_walker = function( &$elements ) use ( &$contact_walker ) {
                foreach ( $elements as $k => &$el ) {
                    // Eradicate Platform Model heading and description from left column
                    if ( ( $el['id'] ?? '' ) === 'fd17972' || ( $el['id'] ?? '' ) === '7696a58' || stripos( json_encode( $el ), 'Private Investment Platform' ) !== false ) {
                        if ( ( $el['widgetType'] ?? '' ) !== 'form' ) {
                            unset( $elements[$k] );
                            continue;
                        }
                    }

                    // Form Widget: Add LinkedIn Profile and strip placeholders from normal text inputs
                    if ( ( $el['id'] ?? '' ) === '0b81066' || ( $el['widgetType'] ?? '' ) === 'form' ) {
                        if ( ! empty( $el['settings']['form_fields'] ) ) {
                            $has_linkedin = false;
                            $fields = [];
                            foreach ( $el['settings']['form_fields'] as $ff ) {
                                // Strip placeholders from text, email, url, textarea (retain on select)
                                if ( in_array( $ff['field_type'] ?? '', [ 'text', 'email', 'url', 'textarea' ] ) ) {
                                    $ff['placeholder'] = '';
                                }
                                if ( ( $ff['custom_id'] ?? '' ) === 'linkedin' || ( $ff['_id'] ?? '' ) === 'linkedin_fld' ) {
                                    $has_linkedin = true;
                                }
                                $fields[] = $ff;
                                // Insert LinkedIn right after website field
                                if ( ( $ff['custom_id'] ?? '' ) === 'website' && ! $has_linkedin ) {
                                    $fields[] = [
                                        '_id'         => 'linkedin_fld',
                                        'custom_id'   => 'linkedin',
                                        'field_type'  => 'url',
                                        'field_label' => 'LinkedIn Profile',
                                        'placeholder' => '',
                                        'width'       => '50',
                                        'required'    => '',
                                    ];
                                    $has_linkedin = true;
                                }
                            }
                            $el['settings']['form_fields'] = $fields;
                        }
                    }

                    if ( ! empty( $el['elements'] ) ) {
                        $contact_walker( $el['elements'] );
                    }
                }
                $elements = array_values( $elements );
            };
            $contact_walker( $contact_data );
            update_post_meta( $contact_id, '_elementor_data', wp_slash( json_encode( $contact_data ) ) );
        }
    }

    // Global Eradication of "Private Investment Platform" from DB postmeta & posts
    global $wpdb;
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'Private Investment Platform &middot; India', '')" );
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'Private Investment Platform · India', '')" );
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'Private Investment Platform', 'G Das Ventures')" );
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'http://localhost/wordpress/', 'https://gdasventures.com/')" );
    $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'https://grey-tapir-780392.hostingersite.com/', 'https://gdasventures.com/')" );

    $wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'Private Investment Platform &middot; India', '')" );
    $wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'Private Investment Platform · India', '')" );
    $wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'Private Investment Platform', 'G Das Ventures')" );

    // Clear Elementor CSS cache & regenerate Kit CSS
    if ( class_exists( '\Elementor\Plugin' ) ) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
            $kit_css = new \Elementor\Core\Files\CSS\Post( 10 );
            $kit_css->update();
        }
    }

    update_option( 'gdas_native_elementor_v5_synced', time() );
}
