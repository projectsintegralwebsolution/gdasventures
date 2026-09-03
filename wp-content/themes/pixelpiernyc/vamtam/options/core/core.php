<?php

/**
 * Controls attached to core sections
 *
 * @package vamtam/pixelpiernyc
 */


return array(
	array(
		'label'     => esc_html__( 'Header Logo Type', 'pixelpiernyc' ),
		'id'        => 'header-logo-type',
		'type'      => 'switch',
		'transport' => 'postMessage',
		'section'   => 'title_tagline',
		'choices'   => array(
			'image'      => esc_html__( 'Image', 'pixelpiernyc' ),
			'site-title' => esc_html__( 'Site Title', 'pixelpiernyc' ),
		),
		'priority' => 8,
	),

	array(
		'label'     => esc_html__( 'Single Product Image Zoom', 'pixelpiernyc' ),
		'id'        => 'wc-product-gallery-zoom',
		'type'      => 'switch',
		'transport' => 'postMessage',
		'section'   => 'woocommerce_product_images',
		'choices'   => array(
			'enabled'  => esc_html__( 'Enabled', 'pixelpiernyc' ),
			'disabled' => esc_html__( 'Disabled', 'pixelpiernyc' ),
		),
		// 'active_callback' => 'vamtam_extra_features',
	),
);


