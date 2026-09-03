<?php
namespace VamtamElementor\DocumentSettings\PageSettings;

use Elementor\Core\DocumentTypes\PageBase as PageBase;
use Elementor\Modules\Library\Documents\Page as LibraryPageDocument;


function vamtam_add_page_scroll_direction_control( $document ) {
	if ( ! $document instanceof PageBase && ! $document instanceof LibraryPageDocument ) {
		return;
	}

	$document->start_injection( [
		'of' => 'post_status',
		'fallback' => [
			'of' => 'post_title',
		],
	] );

    // Scroll Direction
    $document->add_control(
        'vamtam_page_scroll_direction',
        [
            'label' => esc_html__( 'Page Scroll Direction', 'vamtam-elementor-integration' ),
			'description' => esc_html__( 'Choose the scroll direction for this page (desktop-only). To preview changes, switch to frontend.', 'vamtam-elementor-integration' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'vertical'   => esc_html__( 'Vertical', 'vamtam-elementor-integration' ),
                'horizontal' => esc_html__( 'Horizontal', 'vamtam-elementor-integration' ),
            ],
            'default' => 'vertical',
			'condition' => [
				'template' => 'elementor_canvas',
			],
        ]
    );

	$document->end_injection();
}

add_action( 'elementor/documents/register_controls', __NAMESPACE__ . '\vamtam_add_page_scroll_direction_control' );
