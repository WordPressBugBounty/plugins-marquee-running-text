<?php
/**
 * Main plugin bootstrap.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin files and wires up the hooks.
 */
final class MRText_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var MRText_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns the plugin instance.
	 *
	 * @return MRText_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();

		add_action( 'init', array( 'MRText_Post_Type', 'register' ), 5 );
		add_action( 'init', array( 'MRText_Frontend', 'register_assets' ), 5 );
		add_action( 'init', array( 'MRText_Migration', 'maybe_run' ), 20 );

		MRText_Frontend::init();
		MRText_Block::init();
		MRText_Elementor::init();

		if ( is_admin() ) {
			MRText_Admin::init();
			MRText_Admin_Editor::init();
		}

		/**
		 * Fires after Marquee Running Text has loaded. Add-ons hook in here.
		 */
		do_action( 'mrtext_loaded' );
	}

	/**
	 * Removes plugin data after uninstall, only when the user opted in
	 * (Marquee → Settings → "Delete all marquees and settings").
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) {
				switch_to_blog( $site_id );
				self::uninstall_site();
				restore_current_blog();
			}
		} else {
			self::uninstall_site();
		}
	}

	/**
	 * Deletes the plugin data of the current site if the user opted in.
	 */
	private static function uninstall_site() {
		$settings = get_option( 'mrtext_settings', array() );
		if ( ! is_array( $settings ) || empty( $settings['delete_data'] ) ) {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'   => 'mrtext_marquee',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}

		$options = array(
			'mrtext_settings',
			'mrtext_db_version',
			'mrtext_migration_lock',
			'mrtext_migration_notice',
			// Version 1.x options.
			'mrtext_bg_color_option',
			'mrtext_color_option',
			'mrtext_hover_color_option',
			'mrtext_font_size',
			'mrtext_font_weight',
			'mrtext_font_direction',
			'mrtext_font_scroll_delay',
			'mrtext_radio',
			'mrtext_radio_before_after',
		);
		for ( $i = 1; $i <= 5; $i++ ) {
			$options[] = 'mrtext_text_field_' . $i;
			$options[] = 'mrtext_text_field_' . $i . '_link';
		}
		foreach ( $options as $option ) {
			delete_option( $option );
		}

		delete_metadata( 'user', 0, 'mrt_dismissed_notice', '', true );
	}

	/**
	 * Includes the plugin classes.
	 */
	private function includes() {
		$dir = MRTEXT_DIR . 'includes/';

		require_once $dir . 'class-mrtext-config.php';
		require_once $dir . 'class-mrtext-settings.php';
		require_once $dir . 'class-mrtext-post-type.php';
		require_once $dir . 'class-mrtext-migration.php';
		require_once $dir . 'class-mrtext-renderer.php';
		require_once $dir . 'class-mrtext-frontend.php';
		require_once $dir . 'class-mrtext-block.php';
		require_once $dir . 'class-mrtext-elementor.php';

		if ( is_admin() ) {
			require_once $dir . 'admin/class-mrtext-admin.php';
			require_once $dir . 'admin/class-mrtext-admin-editor.php';
		}
	}
}
