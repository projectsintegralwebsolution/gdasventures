<?php
namespace VamtamElementor\Widgets\ImageCarousel;

// Extending the Image Carousel widget.

// Theme preferences.
if ( ! \Vamtam_Elementor_Utils::is_widget_mod_active( 'image-carousel' ) ) {
	return;
}

// Is Pro Widget.
if ( ! \VamtamElementorIntregration::is_elementor_pro_active() ) {
	return;
}

if ( vamtam_theme_supports( 'image-carousel--nav-spacing' ) ) {
	function update_navigation_control( $controls_manager, $widget ) {
		// Navigation
		\Vamtam_Elementor_Utils::add_control_options( $controls_manager, $widget, 'navigation', [
			'prefix_class' => 'vamtam-nav-',
		] );
	}

	// Content - Image Carousel.
	function section_image_carousel_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		update_navigation_control( $controls_manager, $widget );
	}
	add_action( 'elementor/element/image-carousel/section_image_carousel/before_section_end', __NAMESPACE__ . '\section_image_carousel_before_section_end', 10, 2 );

	function add_nav_spacing_control( $controls_manager, $widget ) {
		$widget->add_responsive_control(
			'vamtam_nav_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementor-pro' ),
				'type' => $controls_manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
                    'size' => 60,
                ],
				'range' => [
					'px' => [
						'max' => 100,
					],
				],
				'condition' => [
					'navigation' => [ 'dots', 'both' ],
					'dots_position' => 'outside',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--vamtam-nav-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);
	}

	// Style - Navigation.
	function section_style_navigation_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		add_nav_spacing_control( $controls_manager, $widget );
	}
	add_action( 'elementor/element/image-carousel/section_style_navigation/before_section_end', __NAMESPACE__ . '\section_style_navigation_before_section_end', 10, 2 );
}
