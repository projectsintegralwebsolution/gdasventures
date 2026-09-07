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

// Automatic One-Time Sync for 100% Native Elementor Architecture
add_action( 'init', 'gdas_sync_native_elementor_data' );
function gdas_sync_native_elementor_data() {
    if ( get_option( 'gdas_native_elementor_v2_synced' ) ) {
        return;
    }

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
                        $has_b = false;
                        foreach ( $el['elements'] as $sub ) {
                            if ( ( $sub['id'] ?? '' ) === 'gdas_contact_guarantees' ) {
                                $has_b = true;
                                break;
                            }
                        }
                        if ( ! $has_b ) {
                            $badges_c = [
                                'id' => 'gdas_contact_guarantees',
                                'elType' => 'container',
                                'settings' => [
                                    'content_width' => 'full',
                                    'flex_direction' => 'row',
                                    'flex_wrap' => 'nowrap',
                                    'flex_justify_content' => 'space-between',
                                    'flex_gap' => ['unit' => 'px', 'size' => 12],
                                    'margin' => ['unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '24', 'left' => '0']
                                ],
                                'elements' => [
                                    [
                                        'id' => 'guar_1',
                                        'elType' => 'widget',
                                        'widgetType' => 'icon-box',
                                        'settings' => [
                                            'selected_icon' => ['value' => 'fas fa-user-shield', 'library' => 'fa-solid'],
                                            'title_text' => 'Direct Partner Review',
                                            'title_size' => 'h6',
                                            'position' => 'left',
                                            'view' => 'default',
                                            'primary_color' => '#B88E44'
                                        ]
                                    ],
                                    [
                                        'id' => 'guar_2',
                                        'elType' => 'widget',
                                        'widgetType' => 'icon-box',
                                        'settings' => [
                                            'selected_icon' => ['value' => 'fas fa-lock', 'library' => 'fa-solid'],
                                            'title_text' => 'Strict Confidentiality',
                                            'title_size' => 'h6',
                                            'position' => 'left',
                                            'view' => 'default',
                                            'primary_color' => '#B88E44'
                                        ]
                                    ],
                                    [
                                        'id' => 'guar_3',
                                        'elType' => 'widget',
                                        'widgetType' => 'icon-box',
                                        'settings' => [
                                            'selected_icon' => ['value' => 'fas fa-bolt', 'library' => 'fa-solid'],
                                            'title_text' => 'Rapid Response',
                                            'title_size' => 'h6',
                                            'position' => 'left',
                                            'view' => 'default',
                                            'primary_color' => '#B88E44'
                                        ]
                                    ]
                                ]
                            ];
                            $form_idx = count( $el['elements'] ) - 1;
                            array_splice( $el['elements'], $form_idx, 0, [$badges_c] );
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
                                        'secondary_color' => '#B88E44'
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

    // Clear Elementor CSS cache
    if ( class_exists( '\Elementor\Plugin' ) ) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
    }

    update_option( 'gdas_native_elementor_v2_synced', time() );
}
