<?php
namespace VamtamElementor\Widgets\Button;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Theme preferences.
if ( ! \Vamtam_Elementor_Utils::is_widget_mod_active( 'button' ) ) {
	return;
}

if ( vamtam_theme_supports( 'button--icon-size-control' ) ) {
	function add_icon_size_control( $controls_manager, $widget ) {
		$widget->start_injection( [
			'of' => 'selected_icon',
		] );
		$widget->add_control(
			'vamtam_icon_size',
			[
				'label' => __( 'Size', 'vamtam-elementor-integration' ),
				'type' => $controls_manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 300,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-button-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'selected_icon[value]!' => '',
				],
			]
		);
		$widget->end_injection();
	}
	// Content - Button section
	function section_button_content_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		add_icon_size_control( $controls_manager, $widget );
	}
	add_action( 'elementor/element/button/section_button/before_section_end', __NAMESPACE__ . '\section_button_content_before_section_end', 10, 2 );
}
