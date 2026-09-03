<?php
/**
 * Post metadata template
 *
 * @package vamtam/pixelpiernyc
 */

?>
<?php $tags = get_the_tags(); ?>
<?php if ( $tags ) : ?>
	<div class="post-meta">
		<nav>
			<div class="the-tags vamtam-meta-tax">
				<?php the_tags( '<strong>' . esc_html__( 'Tags:', 'pixelpiernyc' ) . '</strong> ', ', ', '' ); ?>
			</div>
		</nav>
	</div>
<?php endif; ?>
