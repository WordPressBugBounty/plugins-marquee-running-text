<?php
/**
 * Elementor integration.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Elementor widget when Elementor is active.
 */
class MRText_Elementor {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widget' ) );
	}

	/**
	 * Registers the widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public static function register_widget( $widgets_manager ) {
		require_once MRTEXT_DIR . 'includes/class-mrtext-elementor-widget.php';
		$widgets_manager->register( new MRText_Elementor_Widget() );
	}

	/**
	 * Marquee choices for the widget's select control.
	 *
	 * @return array
	 */
	public static function marquee_options() {
		$options = array( 0 => __( 'Default marquee', 'marquee-running-text' ) );
		foreach ( MRText_Post_Type::get_marquees() as $post ) {
			$title = '' !== $post->post_title ? $post->post_title : __( '(no title)', 'marquee-running-text' );
			if ( 'publish' !== $post->post_status ) {
				$title .= ' ' . __( '(inactive)', 'marquee-running-text' );
			}
			$options[ $post->ID ] = $title;
		}
		return $options;
	}
}
