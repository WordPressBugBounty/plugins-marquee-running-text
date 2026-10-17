<?php
/**
 * Block editor (Gutenberg) block.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Marquee Running Text" block.
 */
class MRText_Block {

	const NAME = 'marquee-running-text/marquee';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_data' ) );
	}

	/**
	 * Registers the block from block.json.
	 */
	public static function register() {
		register_block_type(
			MRTEXT_DIR . 'blocks/marquee',
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);
	}

	/**
	 * Server-side render.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$id   = isset( $attributes['marqueeId'] ) ? absint( $attributes['marqueeId'] ) : 0;
		$html = MRText_Frontend::render_inline( $id );

		if ( '' === $html && defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) {
			return '<p class="mrtext-block-empty">' . esc_html__( 'This marquee is inactive or has no items yet.', 'marquee-running-text' ) . '</p>';
		}

		if ( '' === $html ) {
			return '';
		}

		$wrapper = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes() : '';
		return '<div ' . $wrapper . '>' . $html . '</div>';
	}

	/**
	 * Passes the list of marquees to the block editor script.
	 */
	public static function editor_data() {
		$marquees = array();
		foreach ( MRText_Post_Type::get_marquees() as $post ) {
			$marquees[] = array(
				'id'     => $post->ID,
				'title'  => '' !== $post->post_title ? $post->post_title : __( '(no title)', 'marquee-running-text' ),
				'active' => 'publish' === $post->post_status,
			);
		}

		$handle = generate_block_asset_handle( self::NAME, 'editorScript' );
		$data   = array(
			'marquees'  => $marquees,
			'manageUrl' => current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=mrtext-settings' ) : '',
		);

		wp_add_inline_script( $handle, 'window.mrtextBlockData = ' . wp_json_encode( $data ) . ';', 'before' );
		wp_set_script_translations( $handle, 'marquee-running-text' );
	}
}
