<?php
/**
 * OpsXpress theme setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme supports.
 *
 * Note: 'wp-block-styles' is intentionally NOT enabled. It loads WordPress'
 * opinionated block styles (quote borders, list spacing, etc.) which would
 * alter the existing design once real blocks are used.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_editor_style( array( 'style.css', 'assets/css/editor.css' ) );

		add_theme_support(
			'custom-logo',
			array(
				'height'      => 64,
				'width'       => 220,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);

		add_theme_support(
			'html5',
			array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);
	}
);

/**
 * Front-end assets.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$version = wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'opsxpress-fonts',
			'[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap)',
			array(),
			null
		);

		wp_enqueue_style(
			'opsxpress-style',
			get_stylesheet_uri(),
			array( 'opsxpress-fonts' ),
			$version
		);

		wp_enqueue_script(
			'opsxpress-main',
			get_theme_file_uri( '/assets/js/main.js' ),
			array(),
			$version,
			true
		);
	}
);

/**
 * Preconnect to the font CDN so the corrected font URL resolves quickly.
 */
add_filter(
	'wp_resource_hints',
	function ( $hints, $relation ) {
		if ( 'preconnect' === $relation ) {
			$hints[] = array( 'href' => '[fonts.gstatic.com](https://fonts.gstatic.com)', 'crossorigin' );
		}
		return $hints;
	},
	10,
	2
);

/**
 * Import assets/logo/logo.png into the media library once and set it as the
 * site logo. This keeps the logo path installation-independent (it works in
 * the /wordpress/ subdirectory) and makes it swappable from the Site Editor.
 */
function opsxpress_provision_logo() {
	if ( get_theme_mod( 'custom_logo' ) || get_option( 'opsxpress_logo_provisioned' ) ) {
		return;
	}

	// Set the guard first so a failure never retries on every request.
	update_option( 'opsxpress_logo_provisioned', 1 );

	$source = get_theme_file_path( '/assets/logo/logo.png' );

	if ( ! file_exists( $source ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_bits( 'opsxpress-logo.png', null, file_get_contents( $source ) );

	if ( ! empty( $upload['error'] ) ) {
		return;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'OpsXpress logo',
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return;
	}

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
	);

	set_theme_mod( 'custom_logo', $attachment_id );
}
add_action( 'after_switch_theme', 'opsxpress_provision_logo' );
add_action( 'admin_init', 'opsxpress_provision_logo' );
