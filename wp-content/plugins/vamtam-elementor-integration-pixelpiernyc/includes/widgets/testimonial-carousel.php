<?php
namespace VamtamElementor\Widgets\TestimonialCarousel;

// Extending the Testimonial Carousel widget.

// Theme preferences.
if ( ! \Vamtam_Elementor_Utils::is_widget_mod_active( 'testimonial-carousel' ) ) {
	return;
}

// Is Pro Widget.
if ( ! \VamtamElementorIntregration::is_elementor_pro_active() ) {
	return;
}

if ( vamtam_theme_supports( 'testimonial-carousel--nav-spacing' ) ) {
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
					'pagination' => 'bullets',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--vamtam-nav-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);
	}

	// Style - Navigation.
	function section_navigation_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		add_nav_spacing_control( $controls_manager, $widget );
	}
	add_action( 'elementor/element/testimonial-carousel/section_navigation/before_section_end', __NAMESPACE__ . '\section_navigation_before_section_end', 10, 2 );
}
