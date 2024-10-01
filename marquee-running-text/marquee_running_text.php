<?php
/*
Plugin Name: Marquee Running Text
Plugin URI: https://bongodevs.com/
Description: Marquee Running Text plugin allows to make <strong> Marquee text at the top header</strong>, with fully customizable options. Most of all, it had to be responsive.
Version: 1.1.2
Requires at least: 5.0
Requires PHP: 5.6
Author: Jahid Hasan
Author URI: http://bongdevs.com/about
License: GPLv2 or later
Text Domain: mrtext
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

function mrtext_enqueue_scripts()
{
    wp_enqueue_style(
        "mrtext-main",
        plugin_dir_url(__FILE__) . "assets/css/mrtext-main.css"
    );
}
add_action("wp_enqueue_scripts", "mrtext_enqueue_scripts");

function mrtext_admin_enqueue_scripts()
{
    wp_enqueue_style(
        "mrtext-admin",
        plugin_dir_url(__FILE__) . "assets/css/mrtext-admin.css"
    );
}
add_action("admin_enqueue_scripts", "mrtext_admin_enqueue_scripts");


/*
 * Settings Link
 */
function mrtext_action_links( $links ) {

	$links = array_merge( array(
		'<a href="' . esc_url( admin_url( 'admin.php?page=mrtext-settings' ) ) . '">' . __( 'Settings', 'mrtext' ) . '</a>'
	), $links );

	return $links;

}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mrtext_action_links' );

/*
 * Plugin shortcode create
 */
    require_once('modules/shortcode.php');
/*
 * add marquee to header
 */

 function mrtext_add_marquee_header()
 {
     $current_value = get_option("mrtext_radio", "show");
     if ($current_value == "show") {
         require_once('modules/marquee.php');
     }
 }
 add_action("wp_head", "mrtext_add_marquee_header", 20);
 

/*
 * Plugin Option Page Function
 */

 require_once('modules/options_page.php');
/*
 * Plugin Callback
 */

 require_once('modules/callback_function.php');
 ?>
