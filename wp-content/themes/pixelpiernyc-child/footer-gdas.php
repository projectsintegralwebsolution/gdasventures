<?php
/**
 * Shared Footer for G Das Ventures
 * Perfectly Aligned Layout with Transparent Light Logo
 */
?>
</main><!-- #gdas-main-content -->

<footer class="gdas-footer">
    <div class="gdas-container">
        <!-- Main Footer Row (Balanced 4 Columns) -->
        <div class="gdas-footer-columns">
            <!-- Col 1: Brand & Tagline -->
            <div class="gdas-footer-brand-col">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="gdas-footer-logo-link">
                    <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-light.png' ); ?>" alt="G Das Ventures" class="gdas-footer-logo" width="220" height="48">
                </a>
                <p class="gdas-footer-tagline">
                    Backing India’s Next Generation of Builders.
                </p>
                <div class="gdas-footer-manifesto">
                    <span>For profit.</span>
                    <span>For good.</span>
                    <span>For India.</span>
                </div>
            </div>

            <!-- Col 2: Navigation -->
            <div class="gdas-footer-nav-col">
                <h4 class="gdas-footer-heading">Navigation</h4>
                <ul class="gdas-footer-list">
                    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/investments/' ) ); ?>">Investments</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/our-perspective/' ) ); ?>">Our Perspective</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Us</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact & Pitch</a></li>
                </ul>
            </div>

            <!-- Col 3: Portfolio -->
            <div class="gdas-footer-nav-col">
                <h4 class="gdas-footer-heading">Portfolio</h4>
                <ul class="gdas-footer-list">
                    <li><a href="https://www.kinecogroup.com/" target="_blank" rel="noopener">Kineco ↗</a></li>
                    <li><a href="https://core.co.in/" target="_blank" rel="noopener">CORE Energy ↗</a></li>
                    <li><a href="https://airlifegases.com/india/" target="_blank" rel="noopener">AirLife Gases ↗</a></li>
                    <li><a href="https://agnikul.in/" target="_blank" rel="noopener">Agnikul Cosmos ↗</a></li>
                </ul>
            </div>

            <!-- Col 4: Connect & Platform -->
            <div class="gdas-footer-nav-col">
                <h4 class="gdas-footer-heading">Connect</h4>
                <ul class="gdas-footer-list">
                    <li><a href="mailto:contact@gdasventures.com">contact@gdasventures.com</a></li>
                    <li><a href="https://linkedin.com" target="_blank" rel="noopener">LinkedIn Profile ↗</a></li>
                    <li><span class="gdas-footer-badge">Private Investment Platform</span></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="gdas-footer-cta-link">Introduce Company →</a></li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom Bar (Disclaimer & Copyright) -->
        <div class="gdas-footer-bottom-bar">
            <div class="gdas-footer-disclaimer-text">
                <strong>Disclaimer:</strong> The information presented on this website is for general informational purposes only. It does not constitute investment advice, an offer to sell, or a solicitation to purchase any security. References to investments do not indicate future performance.
            </div>
            <div class="gdas-footer-copyright-text">
                &copy; <?php echo date( 'Y' ); ?> GDas Ventures. All rights reserved.
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>