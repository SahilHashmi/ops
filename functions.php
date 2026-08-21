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
		add_editor_style(
			array(
				'https://fonts.googleapis.com/css2?family=Barlow:wght@500;700&family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap',
				'style.css',
				'assets/css/editor.css',
			)
		);

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
 * Placeholder used for theme asset URLs inside block template markup.
 *
 * Block templates are static HTML, so they cannot call get_theme_file_uri().
 * Templates reference assets as %THEME_URI%/assets/... and the placeholder is
 * resolved against the active theme at runtime. That keeps the markup free of
 * the theme folder name, so the same files work locally, on staging, on the
 * live server, and after the theme directory is renamed or copied.
 */
define( 'OPSXPRESS_ASSET_PLACEHOLDER', '%THEME_URI%' );

/**
 * Builds the URL of a theme asset, preferring a child theme override.
 *
 * @param string $relative_path Theme-relative asset path, possibly URL encoded.
 * @return string
 */
function opsxpress_theme_asset_uri( $relative_path ) {
	$relative_path = ltrim( $relative_path, '/' );

	if ( '' === $relative_path ) {
		return untrailingslashit( get_stylesheet_directory_uri() );
	}

	// File lookups need the decoded name; the URL keeps its encoded form.
	$decoded = rawurldecode( $relative_path );

	if ( file_exists( get_stylesheet_directory() . '/' . $decoded ) ) {
		return get_stylesheet_directory_uri() . '/' . $relative_path;
	}

	return get_template_directory_uri() . '/' . $relative_path;
}

/**
 * Replaces asset placeholders with URLs of the currently active theme.
 *
 * @param string $content Markup that may contain placeholders.
 * @return string
 */
function opsxpress_resolve_asset_placeholders( $content ) {
	if ( ! is_string( $content ) || false === strpos( $content, OPSXPRESS_ASSET_PLACEHOLDER ) ) {
		return $content;
	}

	return preg_replace_callback(
		'#' . preg_quote( OPSXPRESS_ASSET_PLACEHOLDER, '#' ) . '/?([^"\'\s>)]*)#',
		function ( $matches ) {
			return opsxpress_theme_asset_uri( $matches[1] );
		},
		$content
	);
}

/**
 * Resolves placeholders in a single block template or template part.
 *
 * @param WP_Block_Template|null $block_template Template object.
 * @return WP_Block_Template|null
 */
function opsxpress_resolve_block_template_assets( $block_template ) {
	if ( $block_template instanceof WP_Block_Template ) {
		$block_template->content = opsxpress_resolve_asset_placeholders( $block_template->content );
	}

	return $block_template;
}
add_filter( 'get_block_file_template', 'opsxpress_resolve_block_template_assets' );

/**
 * Resolves placeholders in template queries, which the front end and the Site
 * Editor both use to load templates.
 *
 * @param WP_Block_Template[] $query_result Templates found for the query.
 * @return WP_Block_Template[]
 */
add_filter(
	'get_block_templates',
	function ( $query_result ) {
		return array_map( 'opsxpress_resolve_block_template_assets', $query_result );
	}
);

/**
 * Safety net for templates already saved to the database with a placeholder.
 */
add_filter(
	'render_block',
	function ( $block_content ) {
		return opsxpress_resolve_asset_placeholders( $block_content );
	}
);

/**
 * Converts absolute theme URLs back into the placeholder when a template is
 * saved from the Site Editor, so a customised template never stores the URL of
 * the environment it was edited in.
 *
 * @param stdClass $changes Post data prepared for insertion.
 * @return stdClass
 */
function opsxpress_restore_asset_placeholders( $changes ) {
	if ( ! isset( $changes->post_content ) || ! is_string( $changes->post_content ) ) {
		return $changes;
	}

	$theme_urls = array_unique(
		array(
			untrailingslashit( get_stylesheet_directory_uri() ),
			untrailingslashit( get_template_directory_uri() ),
		)
	);

	$changes->post_content = str_replace(
		$theme_urls,
		OPSXPRESS_ASSET_PLACEHOLDER,
		$changes->post_content
	);

	return $changes;
}
add_filter( 'rest_pre_insert_wp_template', 'opsxpress_restore_asset_placeholders' );
add_filter( 'rest_pre_insert_wp_template_part', 'opsxpress_restore_asset_placeholders' );

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
