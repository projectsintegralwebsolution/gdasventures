<?php
/**
 * Template Name: G Das - Contact
 * Fully Compatible with Elementor & Third-Party Elementor Addons
 */
get_template_part( 'header-gdas' );
?>

<div class="gdas-elementor-content-container">
    <?php
    while ( have_posts() ) : the_post();
        the_content();
    endwhile;
    ?>
</div>

<?php
get_template_part( 'footer-gdas' );