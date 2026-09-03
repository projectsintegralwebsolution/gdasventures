<?php
namespace VamtamElementor\Widgets\Heading;

// Extending the Heading widget.

// Theme preferences.
if ( ! \Vamtam_Elementor_Utils::is_widget_mod_active( 'heading' ) ) {
	return;
}

if ( vamtam_theme_supports( 'heading--text-reveal-anim' ) ) {
	function add_theme_anim_controls( $controls_manager, $widget ) {
		// Use Text Reveal Anim.
		$widget->add_control(
			'vamtam_use_text_reveal_anim',
			[
				'label' => __( 'Use Text Reveal Animation', 'vamtam-elementor-integration' ),
				'description' => __( '*Desktop only', 'vamtam-elementor-integration' ),
				'type' => $controls_manager::SWITCHER,
				'prefix_class' => 'vamtam-has-',
				'return_value' => 'text-reveal-anim',
				'render_type' => 'template',
			]
		);
	}

	// Style - Title Section.
	function section_title_style_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		add_theme_anim_controls( $controls_manager, $widget );
	}
	add_action( 'elementor/element/heading/section_title_style/before_section_end', __NAMESPACE__ . '\section_title_style_before_section_end', 10, 2 );

	// Vamtam_Widget_Heading.
	function widgets_registered() {
		class Vamtam_Widget_Heading extends \Elementor\Widget_Heading {
			public $extra_depended_scripts = [
				'vamtam-heading',
			];

			// Extend constructor.
			public function __construct($data = [], $args = null) {
				parent::__construct($data, $args);

				$this->register_assets();

				$this->add_extra_script_depends();
			}

			// Register the assets the widget depends on.
			public function register_assets() {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

				wp_register_script(
					'vamtam-heading',
					VAMTAM_ELEMENTOR_INT_URL . '/assets/js/widgets/heading/vamtam-heading' . $suffix . '.js',
					[
						'elementor-frontend'
					],
					\VamtamElementorIntregration::PLUGIN_VERSION,
					true
				);
			}

			// Assets the widget depends upon.
			public function add_extra_script_depends() {
				// Scripts
				foreach ( $this->extra_depended_scripts as $script ) {
					$this->add_script_depends( $script );
				}
			}
		}

		// Replace current divider widget with our extended version.
		$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
		$widgets_manager->unregister( 'heading' );
		$widgets_manager->register( new Vamtam_Widget_Heading );
	}
	add_action( \Vamtam_Elementor_Utils::get_widgets_registration_hook(), __NAMESPACE__ . '\widgets_registered', 100 );
}
