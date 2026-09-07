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

    wp_send_json_success( [
        'message' => 'Thank you for introducing your company. Our team will review your submission and reach out if there is a mutual fit.'
    ] );
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

// Enrich Inside Pages with SPG Leaders Iconography
add_filter( 'the_content', 'gdas_enrich_inner_page_icons', 20 );
function gdas_enrich_inner_page_icons( $content ) {
    if ( is_admin() ) {
        return $content;
    }

    // 1. Perspective Page: Conviction Framework 6 Cards
    $perspective_icons = [
        'Founders who know the problem inside out' => 'fa-user-gear',
        'Something genuinely difficult to replicate' => 'fa-shield-halved',
        'A real, urgent customer requirement' => 'fa-bullseye',
        'Tangible evidence that the market cares' => 'fa-chart-line',
        'Room to become meaningfully larger' => 'fa-arrows-to-circle',
        'Thoughtful use of capital' => 'fa-scale-balanced',
    ];
    foreach ( $perspective_icons as $title => $icon ) {
        if ( strpos( $content, $title ) !== false && strpos( $content, $icon ) === false ) {
            $icon_badge = '<div class="gdas-framework-icon-wrap"><i class="fa-solid ' . esc_attr( $icon ) . '"></i></div>';
            $content = preg_replace(
                '/(<h[34][^>]*>\s*' . preg_quote( $title, '/' ) . ')/is',
                $icon_badge . '$1',
                $content,
                1
            );
        }
    }

    // 2. Investments Page: Sector Badges & Meta Highlights
    $sector_icons = [
        'ADVANCED COMPOSITES · AEROSPACE · DEFENCE' => [
            'icon' => 'fa-jet-fighter-up',
            'meta' => [
                ['fa-industry', 'Advanced Composites'],
                ['fa-location-dot', 'Goa, India'],
                ['fa-certificate', 'Ariane 5 & Defence Certified'],
            ]
        ],
        'NUCLEAR ENERGY · HEAVY ENGINEERING' => [
            'icon' => 'fa-atom',
            'meta' => [
                ['fa-atom', 'Heavy Nuclear Equipment'],
                ['fa-location-dot', 'India'],
                ['fa-bolt', 'NPCIL Base-Load Power'],
            ]
        ],
        'SPECIALTY GASES · INFRASTRUCTURE' => [
            'icon' => 'fa-flask-vial',
            'meta' => [
                ['fa-flask-vial', 'High-Purity Helium & Rare Gases'],
                ['fa-location-dot', 'India'],
                ['fa-microchip', 'MRI, Tech & Semiconductors'],
            ]
        ],
        'SPACE TECHNOLOGY · LAUNCH VEHICLES' => [
            'icon' => 'fa-rocket',
            'meta' => [
                ['fa-rocket', 'Orbital Small-Sat Launchers'],
                ['fa-location-dot', 'Chennai, India'],
                ['fa-fire', '3D-Printed Semi-Cryo Engine'],
            ]
        ],
    ];
    foreach ( $sector_icons as $badgeText => $data ) {
        if ( strpos( $content, $badgeText ) !== false && strpos( $content, 'gdas-sector-icon-badge"><i class="fa-solid ' . $data['icon'] ) === false ) {
            $content = preg_replace(
                '/(<h6[^>]*>)\s*' . preg_quote( $badgeText, '/' ) . '(\s*<\/h6>)/is',
                '$1<span class="gdas-sector-icon-badge"><i class="fa-solid ' . esc_attr( $data['icon'] ) . '"></i></span> ' . $badgeText . '$2',
                $content,
                1
            );
            $metaHtml = '<div class="gdas-portfolio-meta-bar">';
            foreach ( $data['meta'] as $item ) {
                $metaHtml .= '<span class="gdas-meta-pill"><i class="fa-solid ' . esc_attr( $item[0] ) . '"></i> ' . esc_html( $item[1] ) . '</span>';
            }
            $metaHtml .= '</div>';

            if ( strpos( $content, $data['meta'][0][1] ) === false ) {
                $content = preg_replace(
                    '/(<div[^>]*elementor-widget-button[^>]*>)/is',
                    $metaHtml . '$1',
                    $content,
                    1
                );
            }
        }
    }

    // 3. Contact Page: Email, Platform, LinkedIn, and Assurance Badges
    if ( strpos( $content, 'contact@gdasventures.com' ) !== false && strpos( $content, 'fa-envelope' ) === false ) {
        $content = preg_replace(
            '/<h4[^>]*>\s*contact@gdasventures\.com\s*<\/h4>/is',
            '<div class="gdas-contact-item-row"><div class="gdas-contact-icon-bubble"><i class="fa-solid fa-envelope"></i></div><div class="gdas-contact-info-block"><span class="gdas-contact-sublbl">DIRECT INQUIRIES</span><a href="mailto:contact@gdasventures.com" class="gdas-contact-link">contact@gdasventures.com</a></div></div>',
            $content,
            1
        );
    }
    if ( strpos( $content, 'Private Investment Platform · India' ) !== false && strpos( $content, 'fa-landmark' ) === false ) {
        $content = preg_replace(
            '/<p[^>]*>\s*Private Investment Platform · India\s*<\/p>/is',
            '<div class="gdas-contact-item-row"><div class="gdas-contact-icon-bubble"><i class="fa-solid fa-landmark"></i></div><div class="gdas-contact-info-block"><span class="gdas-contact-sublbl">PLATFORM MODEL</span><span class="gdas-contact-val">Private Investment Platform · India</span></div></div>',
            $content,
            1
        );
    }
    if ( strpos( $content, 'LinkedIn Profile ↗' ) !== false && strpos( $content, 'fa-linkedin' ) === false ) {
        $content = preg_replace(
            '/<span class="elementor-button-text">LinkedIn Profile ↗<\/span>/is',
            '<span class="elementor-button-text"><i class="fa-brands fa-linkedin" style="margin-right: 0.5rem;"></i> LinkedIn Profile ↗</span>',
            $content,
            1
        );
    }
    if ( strpos( $content, 'Introduce Your Company' ) !== false && strpos( $content, 'gdas-founder-guarantees-grid' ) === false ) {
        $guarantees = '<div class="gdas-founder-guarantees-grid">
            <div class="gdas-guarantee-card"><i class="fa-solid fa-user-shield"></i><span>Direct Partner Review</span></div>
            <div class="gdas-guarantee-card"><i class="fa-solid fa-lock"></i><span>Strict Confidentiality</span></div>
            <div class="gdas-guarantee-card"><i class="fa-solid fa-bolt"></i><span>Rapid Response</span></div>
        </div>';
        $content = preg_replace(
            '/(<\/div>\s*<\/div>\s*<\/div>\s*<div[^>]*data-id="29d9cf4")/is',
            $guarantees . '$1',
            $content,
            1
        );
    }

    // 4. About Page: Operating Principles Grid
    if ( strpos( $content, 'ABOUT GDAS VENTURES' ) !== false && strpos( $content, 'gdas-about-pillars-section' ) === false ) {
        $about_pillars = '
        <section class="gdas-about-pillars-section">
            <div class="gdas-container">
                <div class="gdas-eyebrow">OUR OPERATING PRINCIPLES</div>
                <h2 class="gdas-pillars-title">What Guides Our Capital</h2>
                <p class="gdas-pillars-subhead">We underwrite foundational enterprises with a disciplined, patient, and founder-aligned mandate.</p>
                <div class="gdas-pillars-grid">
                    <div class="gdas-pillar-card">
                        <div class="gdas-pillar-icon-box"><i class="fa-solid fa-hourglass-half"></i></div>
                        <h3>Patient Horizon</h3>
                        <p>We deploy proprietary, permanent capital with multi-decade holding periods, free from artificial fund exit constraints.</p>
                    </div>
                    <div class="gdas-pillar-card">
                        <div class="gdas-pillar-icon-box"><i class="fa-solid fa-shield-halved"></i></div>
                        <h3>Sovereign Capability</h3>
                        <p>We prioritize enterprises that reinforce India’s industrial backbone—securing vital capabilities across aerospace, energy, and materials.</p>
                    </div>
                    <div class="gdas-pillar-card">
                        <div class="gdas-pillar-icon-box"><i class="fa-solid fa-microchip"></i></div>
                        <h3>Hard Defensibility</h3>
                        <p>We favor physical and deep-tech moats: precision manufacturing, intellectual property, certifications, and operational expertise.</p>
                    </div>
                    <div class="gdas-pillar-card">
                        <div class="gdas-pillar-icon-box"><i class="fa-solid fa-handshake"></i></div>
                        <h3>Founder Alignment</h3>
                        <p>We are partners, not backseat drivers. We respect the extraordinary burden of building and engage with clarity, directness, and speed.</p>
                    </div>
                </div>
            </div>
        </section>';
        $content .= $about_pillars;
    }

    return $content;
}