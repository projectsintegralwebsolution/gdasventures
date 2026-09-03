<?php
namespace VamtamElementor\Widgets\PostsBase;

use ElementorPro\Modules\Posts\Skins\Skin_Classic as Elementor_Posts_Classic_Skin;
use ElementorPro\Modules\ThemeBuilder\Skins\Posts_Archive_Skin_Classic as Elementor_Archive_Posts_Classic_Skin;

// Extending the Posts/Posts_Archive widgets.

// Is Pro Widget.
if ( ! \VamtamElementorIntregration::is_elementor_pro_active() ) {
	return;
}

if ( vamtam_theme_supports( 'posts-base--extra-pagination-controls' ) ) {
    function add_extra_pagination_controls( $controls_manager, $widget ) {
        $widget->start_injection( [
            'of' => 'pagination_color_heading',
            'at' => 'before',
        ] );
        $widget->add_control(
            'show_pagination_border',
            [
                'label' => __( 'Border', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::SWITCHER,
                'label_off' => __( 'Hide', 'vamtam-elementor-integration' ),
                'label_on' => __( 'Show', 'vamtam-elementor-integration' ),
                'default' => 'yes',
                'return_value' => 'yes',
                'prefix_class' => 'elementor-show-pagination-border-',
            ]
        );
        $widget->add_control(
            'pagination_border_color',
            [
                'label' => __( 'Border Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers' => 'border-color: {{VALUE}}',
                ],
                'condition' => [
                    'show_pagination_border' => 'yes',
                ],
            ]
        );
        $widget->add_control(
            'pagination_padding',
            [
                'label' => __( 'Padding', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::SLIDER,
                'range' => [
                    'em' => [
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                    ],
                ],
                'size_units' => [ 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers' => 'padding: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        $widget->end_injection();

        // Due to injection points getting messed up cause of the insertions of the tabs, we add the rest
        // at before_section_end cause otherwise the ACTIVE tab won't show.
        \Vamtam_Elementor_Utils::remove_tabs( $controls_manager, $widget, 'pagination_colors' );
        \Vamtam_Elementor_Utils::remove_control( $controls_manager, $widget, 'pagination_spacing' );
        \Vamtam_Elementor_Utils::remove_control( $controls_manager, $widget, 'pagination_spacing_top' );

        $widget->start_controls_tabs( 'pagination_colors' );

        $widget->start_controls_tab(
            'pagination_color_normal',
            [
                'label' => __( 'Normal', 'vamtam-elementor-integration' ),
            ]
        );

        $widget->add_control(
            'pagination_color',
            [
                'label' => __( 'Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers:not(.dots)' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'pagination_bg_color',
            [
                'label' => __( 'Background Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers:not(.dots)' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'pagination_color_hover',
            [
                'label' => __( 'Hover', 'vamtam-elementor-integration' ),
            ]
        );

        $widget->add_control(
            'pagination_hover_color',
            [
                'label' => __( 'Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination a.page-numbers:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'pagination_hover_bg_color',
            [
                'label' => __( 'Background Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'pagination_color_active',
            [
                'label' => __( 'Active', 'vamtam-elementor-integration' ),
            ]
        );

        $widget->add_control(
            'pagination_active_color',
            [
                'label' => __( 'Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers.current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'pagination_active_bg_color',
            [
                'label' => __( 'Background Color', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination .page-numbers.current' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_responsive_control(
            'pagination_spacing',
            [
                'label' => __( 'Space Between', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::SLIDER,
                'separator' => 'before',
                'default' => [
                    'size' => 10,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    'body:not(.rtl) {{WRAPPER}} .elementor-pagination .page-numbers:not(:first-child)' => 'margin-left: calc( {{SIZE}}{{UNIT}}/2 );',
                    'body:not(.rtl) {{WRAPPER}} .elementor-pagination .page-numbers:not(:last-child)' => 'margin-right: calc( {{SIZE}}{{UNIT}}/2 );',
                    'body.rtl {{WRAPPER}} .elementor-pagination .page-numbers:not(:first-child)' => 'margin-right: calc( {{SIZE}}{{UNIT}}/2 );',
                    'body.rtl {{WRAPPER}} .elementor-pagination .page-numbers:not(:last-child)' => 'margin-left: calc( {{SIZE}}{{UNIT}}/2 );',
                ],
            ]
        );

        $widget->add_responsive_control(
            'pagination_spacing_top',
            [
                'label' => __( 'Spacing', 'vamtam-elementor-integration' ),
                'type' => $controls_manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination' => 'margin-top: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
    }
    // Style - Pagination Section.
    function section_pagination_style_before_section_end( $widget, $args ) {
        $controls_manager = \Elementor\Plugin::instance()->controls_manager;
        add_extra_pagination_controls( $controls_manager, $widget );
    }

    // Theme Settings.
    if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'posts' ) ) {
        add_action( 'elementor/element/posts/section_pagination_style/before_section_end', __NAMESPACE__ . '\section_pagination_style_before_section_end', 10, 2 );
    }
    // Theme Settings.
    if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'archive-posts' ) ) {
        add_action( 'elementor/element/archive-posts/section_pagination_style/before_section_end', __NAMESPACE__ . '\section_pagination_style_before_section_end', 10, 2 );
    }
}

if ( vamtam_theme_supports( [ 'posts-base--display-categories', 'posts-base--display-tags' ] ) ) {
	// We need new Skin for displaying post categories/tags.
	trait Vamtam_PostsBase_Classic_Skin_Overrides  {

		public function get_id() {
			return 'vamtam_classic';
		}

		public function get_title() {
			return esc_html__( 'Classic (Vamtam)', 'vamtam-elementor-integration' );
		}

		protected function render_meta_data() {
			/** @var array $settings e.g. [ 'author', 'date', ... ] */
			$settings = $this->get_instance_value( 'meta_data' );
			if ( empty( $settings ) ) {
				return;
			}
			?>
			<div class="elementor-post__meta-data">
				<?php
				if ( in_array( 'author', $settings ) ) {
					$this->render_author();
				}

				if ( in_array( 'date', $settings ) ) {
					$this->render_date_by_type();
				}

				if ( in_array( 'time', $settings ) ) {
					$this->render_time();
				}

				if ( in_array( 'comments', $settings ) ) {
					$this->render_comments();
				}

				if ( in_array( 'modified', $settings ) ) {
					$this->render_date_by_type( 'modified' );
				}

				if ( vamtam_theme_supports( 'posts-base--display-categories' ) ) {
					if ( in_array( 'vamtam-categories', $settings ) ) {
						$this->render_categories();
					}
				}

				if ( vamtam_theme_supports( 'posts-base--display-tags' ) ) {
					if ( in_array( 'vamtam-tags', $settings ) ) {
						$this->render_tags();
					}
				}
				?>
			</div>
			<?php
		}

		protected function render_categories() {
			?>
			<div class="vamtam-post__categories">
				<?php the_category( ', ' ); ?>
			</div>
			<?php
		}

		protected function render_tags() {
			?>
			<div class="vamtam-post__tags">
				<?php the_tags( '', ', ' ); ?>
			</div>
			<?php
		}

		protected function render_post() {
			$this->render_post_header();
			$this->render_thumbnail();
			$this->render_meta_data();
			$this->render_text_header();
			$this->render_title();
			$this->render_excerpt();
			$this->render_read_more();
			$this->render_text_footer();
			$this->render_post_footer();
		}
	}

	// Posts
	class Skin_Vamtam_Posts_Classic extends Elementor_Posts_Classic_Skin {
		use Vamtam_PostsBase_Classic_Skin_Overrides;
	}
	// Archive Posts
	class Skin_Vamtam_Archive_Posts_Classic extends Elementor_Archive_Posts_Classic_Skin {
		use Vamtam_PostsBase_Classic_Skin_Overrides;
	}

	function update_meta_data_control_for_vamtam_classic_skin( $controls_manager, $widget ) {
		if ( vamtam_theme_supports( 'posts-base--display-categories' ) ) {
			// Meta Data.
			\Vamtam_Elementor_Utils::add_control_options( $controls_manager, $widget, 'vamtam_classic_meta_data', [
				'options' => [
					'vamtam-categories' => esc_html__( 'Categories', 'vamtam-elementor-integration' ),
				],
			] );
		}
		if ( vamtam_theme_supports( 'posts-base--display-tags' ) ) {
			// Meta Data.
			\Vamtam_Elementor_Utils::add_control_options( $controls_manager, $widget, 'vamtam_classic_meta_data', [
				'options' => [
					'vamtam-tags' => esc_html__( 'Tags', 'vamtam-elementor-integration' ),
				],
			] );
		}
    }

	function update_content_padding_control_for_vamtam_classic_skin( $controls_manager, $widget ) {
		// Content Padding.
		\Vamtam_Elementor_Utils::add_control_options( $controls_manager, $widget, 'vamtam_classic_content_padding', [
			'selectors' => [
				'{{WRAPPER}}' => '--vamtam-content-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
			],
		] );
    }

    // Content - Layout Section.
    function section_layout_before_section_end( $widget, $args ) {
        $controls_manager = \Elementor\Plugin::instance()->controls_manager;
        update_meta_data_control_for_vamtam_classic_skin( $controls_manager, $widget );
    }

	// Style - Box Section.
    function section_design_box_before_section_end( $widget, $args ) {
        $controls_manager = \Elementor\Plugin::instance()->controls_manager;
        update_content_padding_control_for_vamtam_classic_skin( $controls_manager, $widget );
    }

	// Theme Settings.
    if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'posts' ) ) {
		add_action( 'elementor/element/posts/section_layout/before_section_end', __NAMESPACE__ . '\section_layout_before_section_end', 11, 2 );
		add_action( 'elementor/element/posts/vamtam_classic_section_design_box/before_section_end', __NAMESPACE__ . '\section_design_box_before_section_end', 11, 2 );
	}
    // Theme Settings.
    if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'archive-posts' ) ) {
		add_action( 'elementor/element/archive-posts/section_layout/before_section_end', __NAMESPACE__ . '\section_layout_before_section_end', 11, 2 );
		add_action( 'elementor/element/archive-posts/vamtam_classic_section_design_box/before_section_end', __NAMESPACE__ . '\section_design_box_before_section_end', 11, 2 );
	}
}

function update_image_controls_for_skin( $controls_manager, $widget, $skin_prefix ) {
	// Image Border Radius.
	\Vamtam_Elementor_Utils::add_control_options( $controls_manager, $widget, "{$skin_prefix}_img_border_radius", [
		'selectors' => [
			'{{WRAPPER}}' => '--vamtam-img-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
		]
	] );
}

// Style - Image Section (Classic Layout) - Before Section End.
function section_design_image_before_section_end_classic_skin( $widget, $args ) {
	$controls_manager = \Elementor\Plugin::instance()->controls_manager;
	update_image_controls_for_skin( $controls_manager, $widget, 'classic' );
}
// Style - Image Section (Vamtam Classic Layout) - Before Section End.
function section_design_image_before_section_end_vamtam_classic_skin( $widget, $args ) {
	$controls_manager = \Elementor\Plugin::instance()->controls_manager;
	update_image_controls_for_skin( $controls_manager, $widget, 'vamtam_classic' );
}
// Theme Settings.
if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'posts' ) ) {
	add_action( 'elementor/element/posts/classic_section_design_image/before_section_end', __NAMESPACE__ . '\section_design_image_before_section_end_classic_skin', 10, 2 );
	add_action( 'elementor/element/posts/vamtam_classic_section_design_image/before_section_end', __NAMESPACE__ . '\section_design_image_before_section_end_vamtam_classic_skin', 10, 2 );
}
// Theme Settings.
if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'archive-posts' ) ) {
	add_action( 'elementor/element/archive-posts/classic_section_design_image/before_section_end', __NAMESPACE__ . '\section_design_image_before_section_end_classic_skin', 10, 2 );
	add_action( 'elementor/element/archive-posts/vamtam_classic_section_design_image/before_section_end', __NAMESPACE__ . '\section_design_image_before_section_end_vamtam_classic_skin', 10, 2 );
}

if ( vamtam_theme_supports( 'posts-base--responsive-image-position' ) ) {
	function update_layout_sections_controls_for_skin( $controls_manager, $widget, $skin_prefix ) {
		// Image Position.
		\Vamtam_Elementor_Utils::remove_control( $controls_manager, $widget, "{$skin_prefix}_thumbnail" );
		// Image Size
		\Vamtam_Elementor_Utils::add_control_options( $controls_manager, $widget, "{$skin_prefix}_image_width", [
			'selectors' => [
				// Increase specificity.
				'{{WRAPPER}}.elementor-element .elementor-widget-container .elementor-post__thumbnail__link' => 'width: {{SIZE}}{{UNIT}};',
			]
		] );

		$widget->start_injection( [
            'of' => "{$skin_prefix}_masonry",
            'at' => 'before',
        ] );

		$widget->add_responsive_control(
			"{$skin_prefix}_thumbnail",
			[
				'label' => __( 'Image Position', 'elementor-pro' ),
				'type' => $controls_manager::SELECT,
				'default' => 'top',
				'options' => [
					'top' => __( 'Top', 'elementor-pro' ),
					'left' => __( 'Left', 'elementor-pro' ),
					'right' => __( 'Right', 'elementor-pro' ),
					'none' => __( 'None', 'elementor-pro' ),
				],
				'prefix_class' => 'elementor-posts--thumbnail%s-',
				'condition' => [ // We need to add the skin condition manually since we removed the control previously.
					'_skin' => "{$skin_prefix}",
				]
			]
		);

        $widget->end_injection();
    }
	// Content - Layout Section (All Skins).
    function section_layout_before_section_end_1( $widget, $args ) {
        $controls_manager = \Elementor\Plugin::instance()->controls_manager;
		$skin_prefix      = $widget->get_name() === 'posts' ? 'classic' : 'archive_classic';

		//We don't have distinct skin actions for this one so we do both classic & vamtam_classic skins here.
        update_layout_sections_controls_for_skin( $controls_manager, $widget, $skin_prefix );
        update_layout_sections_controls_for_skin( $controls_manager, $widget, 'vamtam_classic' );
    }
	// Theme Settings.
    if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'posts' ) ) {
		add_action( 'elementor/element/posts/section_layout/before_section_end', __NAMESPACE__ . '\section_layout_before_section_end_1', 11, 2 );
		// This is not possible: add_action( 'elementor/element/posts/vamtam_classic_section_layout/before_section_end', __NAMESPACE__ . '\section_layout_before_section_end_1', 11, 2 );
    }
    // Theme Settings.
    if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'archive-posts' ) ) {
		add_action( 'elementor/element/archive-posts/section_layout/before_section_end', __NAMESPACE__ . '\section_layout_before_section_end_1', 11, 2 );
		// This is not possible: add_action( 'elementor/element/archive-posts/vamtam_classic_section_layout/before_section_end', __NAMESPACE__ . '\section_layout_before_section_end_1', 11, 2 );
    }
}

// TODO: Temporary fix, remove when fixed: https://github.com/elementor/elementor/issues/12126
if ( vamtam_theme_supports( 'posts-base--404-handling-fix' ) ) {
	function vamtam_bypass_404_handling_on_posts_base_fetch( $bypass, $wp_query ) {
		$is_posts_base_fetch_req = isset( $_GET[ 'vamtam_posts_fetch' ] ) && $_GET[ 'vamtam_posts_fetch' ];
		$is_paged_req            = ( isset( $wp_query->query[ 'page' ] ) && $wp_query->query[ 'page' ] ); // on pages where 'paged' query_arg is used, this shouldn't be an issue. If it is we can just add a check fpr 'paged' as well.

		if (  $is_paged_req && $is_posts_base_fetch_req ) {
			// Bypass 404 handling for posts_base fetch requests (paged urls + vamtam_posts_fetch query_var), so there's no redirection that leads to invalid query results (always getting 1st page results).
			$bypass = true;
		}

		return $bypass;
	}
	// Theme Settings.
	if ( \Vamtam_Elementor_Utils::is_widget_mod_active( 'posts' ) || \Vamtam_Elementor_Utils::is_widget_mod_active( 'archive-posts' ) ) {
		add_filter( 'pre_handle_404', __NAMESPACE__ . '\vamtam_bypass_404_handling_on_posts_base_fetch', 10, 2 );
	}
}
