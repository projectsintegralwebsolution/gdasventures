<?php
/**
 * Main Template File for G Das Ventures
 *
 * @package gdasventures
 */

get_header( 'gdas' );
?>

<main id="primary" class="site-main" style="padding: 140px 0 80px; min-height: 70vh;">
    <div class="gdas-container" style="max-width: 1240px; margin: 0 auto; padding: 0 24px;">
        <?php
        if ( have_posts() ) :
            while ( have_posts() ) :
                the_post();
                the_content();
            endwhile;
        else :
            ?>
            <p><?php esc_html_e( 'No content found.', 'pixelpiernyc-child' ); ?></p>
            <?php
        endif;
        ?>
    </div>
</main>

<?php
get_footer( 'gdas' );
