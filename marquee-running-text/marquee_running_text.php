<?php
/**
 * Plugin Name:       Marquee Running Text
 * Plugin URI:        https://bongdevs.com/marquee-running-text/
 * Description:       Smooth, accessible scrolling announcement bars and news tickers for your header, footer or any page — with a live-preview builder, shortcode, block and Elementor widget.
 * Version:           2.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Bongdevs
 * Author URI:        https://bongdevs.com/about
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       marquee-running-text
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'mrtext_fs' ) ) {
	// Another copy of this plugin is already loaded.
	return;
} else {
	// Keep this check: it makes the function_exists() call above work when both versions are active.
	if ( ! function_exists( 'mrtext_fs' ) ) {
		/**
		 * Freemius SDK instance (licensing, updates and in-dashboard upgrades).
		 *
		 * @return Freemius
		 */
		function mrtext_fs() {
			global $mrtext_fs;

			if ( ! isset( $mrtext_fs ) ) {
				require_once __DIR__ . '/vendor/freemius/start.php';

				$mrtext_fs = fs_dynamic_init(
					array(
						'id'                  => '22454',
						'slug'                => 'marquee-running-text',
						'type'                => 'plugin',
						'public_key'          => 'pk_cb27fa3945468441664e01d2ddce3',
						'is_premium'          => false,
						'has_premium_version' => false,
						'has_addons'          => true,
						'has_paid_plans'      => false,
						'is_org_compliant'    => true,
						'menu'                => array(
							'slug'    => 'mrtext-settings',
							'support' => false,
						),
					)
				);
			}

			return $mrtext_fs;
		}

		mrtext_fs();
		do_action( 'mrtext_fs_loaded' );
	}

	define( 'MRTEXT_VERSION', '2.0.0' );
	define( 'MRTEXT_FILE', __FILE__ );
	define( 'MRTEXT_DIR', plugin_dir_path( __FILE__ ) );
	define( 'MRTEXT_URL', plugin_dir_url( __FILE__ ) );
	define( 'MRTEXT_PRO_URL', 'https://bongdevs.com/marquee-running-text/pricing/' );

	require_once MRTEXT_DIR . 'includes/class-mrtext-plugin.php';

	MRText_Plugin::instance();

	// Freemius reports uninstalls, so cleanup runs on its hook instead of uninstall.php.
	mrtext_fs()->add_action( 'after_uninstall', array( 'MRText_Plugin', 'uninstall' ) );
}
