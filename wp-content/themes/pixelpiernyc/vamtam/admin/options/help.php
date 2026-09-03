<?php
return array(
	'name' => esc_html__( 'Help', 'pixelpiernyc' ),
	'auto' => true,
	'config' => array(

		array(
			'name' => esc_html__( 'Help', 'pixelpiernyc' ),
			'type' => 'title',
			'desc' => '',
		),

		array(
			'name' => esc_html__( 'Help', 'pixelpiernyc' ),
			'type' => 'start',
			'nosave' => true,
		),
//----
		array(
			'type' => 'docs',
		),

			array(
				'type' => 'end',
			),
	),
);
