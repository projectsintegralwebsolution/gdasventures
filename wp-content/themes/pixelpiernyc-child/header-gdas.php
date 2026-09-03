<?php
/**
 * Shared Header for G Das Ventures
 * Features Prominent Logo & Minimalist SPG Leaders Menu
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'gdas-site-body' ); ?>>
<?php wp_body_open(); ?>

<!-- Sticky Navigation Bar -->
<header class="gdas-header" id="gdas-header">
    <div class="gdas-container gdas-header-inner">
        <!-- Prominent Transparent Logo -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="gdas-brand-logo" aria-label="G Das Ventures Home">
            <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.png' ); ?>" alt="G Das Ventures" class="gdas-main-logo-img" width="260" height="58">
        </a>

        <!-- Header Actions: Only Clean SPG Burger Menu Button -->
        <div class="gdas-header-actions">
            <button class="gdas-burger-btn" id="gdas-burger-btn" aria-label="Open Navigation Menu" aria-expanded="false">
                <span class="burger-bars">
                    <span class="b-line b-line-1"></span>
                    <span class="b-line b-line-2"></span>
                </span>
                <span class="burger-text">Menu</span>
            </button>
        </div>
    </div>
</header>

<!-- SPG Leaders Style Fullscreen / Flyout Navigation Drawer -->
<div class="gdas-flyout-overlay" id="gdas-flyout-menu" aria-hidden="true">
    <div class="gdas-flyout-backdrop" id="gdas-flyout-backdrop"></div>
    <div class="gdas-flyout-dialog">
        <!-- Flyout Header -->
        <div class="gdas-flyout-top">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="gdas-flyout-brand">
                <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.png' ); ?>" alt="G Das Ventures" width="240" height="54">
            </a>
            <button class="gdas-flyout-close-btn" id="gdas-flyout-close" aria-label="Close Menu">
                <span class="close-icon">✕</span>
                <span class="close-label">Close</span>
            </button>
        </div>

        <!-- Flyout Content Grid -->
        <div class="gdas-flyout-content-grid">
            <!-- Primary Navigation Links -->
            <div class="gdas-flyout-nav-col">
                <span class="flyout-section-heading">Navigation</span>
                <ul class="gdas-flyout-links">
                    <li class="<?php echo is_front_page() ? 'current' : ''; ?>">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <span class="num">01</span>
                            <span class="label">Home</span>
                        </a>
                    </li>
                    <li class="<?php echo is_page( 'investments' ) ? 'current' : ''; ?>">
                        <a href="<?php echo esc_url( home_url( '/investments/' ) ); ?>">
                            <span class="num">02</span>
                            <span class="label">Investments</span>
                        </a>
                    </li>
                    <li class="<?php echo is_page( 'our-perspective' ) ? 'current' : ''; ?>">
                        <a href="<?php echo esc_url( home_url( '/our-perspective/' ) ); ?>">
                            <span class="num">03</span>
                            <span class="label">Our Perspective</span>
                        </a>
                    </li>
                    <li class="<?php echo is_page( 'about' ) ? 'current' : ''; ?>">
                        <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">
                            <span class="num">04</span>
                            <span class="label">About Us</span>
                        </a>
                    </li>
                    <li class="<?php echo is_page( 'contact' ) ? 'current' : ''; ?>">
                        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
                            <span class="num">05</span>
                            <span class="label">Contact & Pitch</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Portfolio & Direct Context Sidebar -->
            <div class="gdas-flyout-side-col">
                <div class="flyout-side-block">
                    <span class="flyout-section-heading">Portfolio Companies</span>
                    <ul class="flyout-portfolio-links">
                        <li>
                            <a href="https://www.kinecogroup.com/" target="_blank" rel="noopener">
                                <strong>Kineco</strong>
                                <span class="tag">Aerospace & Defence Composites</span>
                                <span class="arr">↗</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://core.co.in/" target="_blank" rel="noopener">
                                <strong>CORE Energy</strong>
                                <span class="tag">Nuclear Energy & Power</span>
                                <span class="arr">↗</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://airlifegases.com/india/" target="_blank" rel="noopener">
                                <strong>AirLife Gases</strong>
                                <span class="tag">Specialty Gases & Infrastructure</span>
                                <span class="arr">↗</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://agnikul.in/" target="_blank" rel="noopener">
                                <strong>Agnikul Cosmos</strong>
                                <span class="tag">Small-Satellite Launch Vehicles</span>
                                <span class="arr">↗</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="flyout-side-block">
                    <span class="flyout-section-heading">Direct Contact</span>
                    <div class="flyout-contact-item">
                        <span class="lbl">Email</span>
                        <a href="mailto:contact@gdasventures.com">contact@gdasventures.com</a>
                    </div>
                    <div class="flyout-contact-item">
                        <span class="lbl">Platform</span>
                        <span>Private Investment Platform · India</span>
                    </div>
                    <div class="flyout-contact-item">
                        <span class="lbl">Professional Network</span>
                        <a href="https://linkedin.com" target="_blank" rel="noopener">LinkedIn Profile ↗</a>
                    </div>
                </div>

                <div class="flyout-creed-box">
                    <span>For profit.</span>
                    <span>For good.</span>
                    <span>For India.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<main id="gdas-main-content">