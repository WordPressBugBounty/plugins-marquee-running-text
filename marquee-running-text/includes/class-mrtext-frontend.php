<?php
/**
 * Front-end output: header/footer placements and the [mrtext] shortcode.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Places marquees on the site.
 */
class MRText_Frontend {

	/**
	 * Whether the top marquees were printed in wp_body_open.
	 *
	 * @var bool
	 */
	private static $printed_top = false;

	/**
	 * Marquees placed on the current page, grouped by "top" and "bottom".
	 *
	 * @var array|null
	 */
	private static $placed = null;

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'print_top' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'print_footer' ), 5 );
		add_shortcode( 'mrtext', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Registers the front-end assets.
	 */
	public static function register_assets() {
		wp_register_style( 'mrtext', MRTEXT_URL . 'assets/css/mrtext.css', array(), MRTEXT_VERSION );
		wp_register_script(
			'mrtext',
			MRTEXT_URL . 'assets/js/mrtext.js',
			array(),
			MRTEXT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueues assets in <head> when this page shows a marquee, so there is no flash of unstyled content.
	 */
	public static function enqueue() {
		$placed = self::get_placed();
		if ( $placed['top'] || $placed['bottom'] ) {
			MRText_Renderer::enqueue_assets();
			return;
		}

		$post = get_queried_object();
		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'mrtext' ) ) {
			MRText_Renderer::enqueue_assets();
		}
	}

	/**
	 * Prints top and sticky marquees right after <body>.
	 */
	public static function print_top() {
		if ( self::$printed_top ) {
			return;
		}
		self::$printed_top = true;

		foreach ( self::get_placed()['top'] as $entry ) {
			echo MRText_Renderer::render( $entry['id'], array( 'position' => $entry['placement'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in MRText_Renderer.
		}
	}

	/**
	 * Prints bottom marquees, plus top marquees for themes that never call wp_body_open().
	 */
	public static function print_footer() {
		$placed = self::get_placed();

		if ( ! self::$printed_top ) {
			self::$printed_top = true;
			foreach ( $placed['top'] as $entry ) {
				$html = MRText_Renderer::render(
					$entry['id'],
					array(
						'position' => $entry['placement'],
						'relocate' => true,
					)
				);
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in MRText_Renderer.
			}
		}

		foreach ( $placed['bottom'] as $entry ) {
			echo MRText_Renderer::render( $entry['id'], array( 'position' => 'bottom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in MRText_Renderer.
		}
	}

	/**
	 * [mrtext] and [mrtext id="123"].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'mrtext' );
		return self::render_inline( absint( $atts['id'] ) );
	}

	/**
	 * Renders an active marquee inline (shortcode, block, Elementor).
	 *
	 * @param int $id Marquee ID; 0 uses the default marquee.
	 * @return string
	 */
	public static function render_inline( $id ) {
		if ( ! $id ) {
			$id = MRText_Post_Type::get_default_id();
		}
		if ( ! $id || 'publish' !== get_post_status( $id ) ) {
			return '';
		}
		return MRText_Renderer::render( $id, array( 'position' => 'inline' ) );
	}

	/**
	 * Whether a marquee with a site placement shows on the current request.
	 *
	 * @param array   $config Marquee config.
	 * @param WP_Post $post   Marquee post.
	 * @return bool
	 */
	public static function should_display( array $config, $post ) {
		$show = true;
		if ( 'front_page' === $config['display']['show_on'] ) {
			$show = is_front_page();
		}

		/**
		 * Filters whether a placed marquee shows on the current request. The Pro add-on adds display conditions here.
		 *
		 * @param bool    $show   Whether to show.
		 * @param array   $config Marquee config.
		 * @param WP_Post $post   Marquee post.
		 */
		return (bool) apply_filters( 'mrtext_should_display', $show, $config, $post );
	}

	/**
	 * Active marquees with a site placement that should show on this request.
	 *
	 * @return array
	 */
	private static function get_placed() {
		if ( null !== self::$placed ) {
			return self::$placed;
		}

		self::$placed = array(
			'top'    => array(),
			'bottom' => array(),
		);

		if ( is_admin() || is_feed() || is_embed() ) {
			return self::$placed;
		}

		foreach ( MRText_Post_Type::get_marquees( 'publish' ) as $post ) {
			$config    = MRText_Post_Type::get_config( $post->ID );
			$placement = $config['display']['placement'];

			if ( 'none' === $placement || ! self::should_display( $config, $post ) ) {
				continue;
			}

			self::$placed[ 'bottom' === $placement ? 'bottom' : 'top' ][] = array(
				'id'        => $post->ID,
				'placement' => $placement,
			);
		}

		return self::$placed;
	}
}
