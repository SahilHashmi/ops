<?php
/**
 * OpsXpress theme setup.
 *
 * @package OpsXpress
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
		add_theme_support( 'post-thumbnails' );
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
 * Returns the theme version, with a safe fallback.
 *
 * @return string
 */
function opsxpress_version() {
	$version = wp_get_theme()->get( 'Version' );

	return $version ? $version : '1.0.0';
}

/**
 * Return a cache-busting version for a theme asset.
 *
 * The Site Editor and the public site both load assets from the same theme.
 * Using the file modification time means CSS and JavaScript changes are shown
 * immediately after saving, instead of waiting for a browser cache to expire.
 *
 * @param string $relative_path Theme-relative asset path.
 * @return string
 */
function opsxpress_asset_version( $relative_path ) {
	$asset_path = get_theme_file_path( $relative_path );

	return file_exists( $asset_path ) ? (string) filemtime( $asset_path ) : opsxpress_version();
}

/**
 * Front-end assets.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$style_version  = opsxpress_asset_version( '/style.css' );
		$script_version = opsxpress_asset_version( '/assets/js/main.js' );

		wp_enqueue_style(
			'opsxpress-fonts',
			'https://fonts.googleapis.com/css2?family=Barlow:wght@500;700&family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'opsxpress-style',
			get_stylesheet_uri(),
			array( 'opsxpress-fonts' ),
			$style_version
		);

		wp_enqueue_script(
			'opsxpress-main',
			get_theme_file_uri( '/assets/js/main.js' ),
			array(),
			$script_version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
);

/**
 * Accessible skip link lives outside the template editor so it never creates
 * an invalid Custom HTML block in the Site Editor.
 */
add_action(
	'wp_body_open',
	function () {
		echo '<a class="skip-link" href="#main-content">Skip to content</a>';
	},
	1
);

/**
 * Flags that JavaScript is available before first paint.
 *
 * Scroll reveals stay visible when scripts are blocked or fail to load,
 * so content can never remain permanently hidden.
 */
add_action(
	'wp_head',
	function () {
		echo "<script>document.documentElement.classList.add('has-js');if('scrollRestoration' in history){history.scrollRestoration='manual';}if(!location.hash){window.addEventListener('pageshow',function(){requestAnimationFrame(function(){window.scrollTo(0,0);});},{once:true});}</script>\n";
	},
	1
);

/**
 * Preconnect to the font CDN so the font files resolve quickly.
 *
 * @param array  $hints    Resource hints.
 * @param string $relation Hint relation type.
 * @return array
 */
add_filter(
	'wp_resource_hints',
	function ( $hints, $relation ) {
		if ( 'preconnect' === $relation ) {
			$hints[] = array(
				'href'        => '[fonts.gstatic.com](https://fonts.gstatic.com)',
				'crossorigin' => 'anonymous',
			);
		}

		return $hints;
	},
	10,
	2
);

/**
 * Performance: only load the styles of the blocks actually used on a page.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Performance: remove the emoji detection script and related requests.
 */
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
);

/**
 * Performance: drop the emoji DNS prefetch hint added by core.
 *
 * @param array  $urls     Resource URLs.
 * @param string $relation Hint relation type.
 * @return array
 */
add_filter(
	'wp_resource_hints',
	function ( $urls, $relation ) {
		if ( 'dns-prefetch' !== $relation ) {
			return $urls;
		}

		foreach ( $urls as $key => $url ) {
			if ( is_string( $url ) && false !== strpos( $url, 's.w.org' ) ) {
				unset( $urls[ $key ] );
			}
		}

		return array_values( $urls );
	},
	20,
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

	if ( ! file_exists( $source ) || ! is_readable( $source ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$contents = file_get_contents( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( false === $contents ) {
		return;
	}

	$upload = wp_upload_bits( 'opsxpress-logo.png', null, $contents );

	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
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
