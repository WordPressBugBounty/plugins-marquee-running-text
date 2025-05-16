<?php
/*
Plugin Name: Marquee Running Text
Plugin URI: https://bongodevs.com/
Description: Marquee Running Text plugin allows to make <strong> Marquee text at the top header</strong>, with fully customizable options. Most of all, it had to be responsive.
Version: 1.1.5
Requires at least: 5.0
Requires PHP: 5.6
Author: Bongdevs
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


/**
 * Add Settings and Pro Upgrade Links
 */
function mrtext_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=mrtext-settings' ) ) . '">' . __( 'Settings', 'mrtext' ) . '</a>';
    $pro_link = '<a href="https://bongdevs.com/wp-assets/marquee-running-text-pro/" target="_blank" style="font-weight: bold; color: #A31C23;">' . __( 'Get Pro', 'mrtext' ) . '</a>';

    array_unshift( $links, $settings_link, $pro_link );

    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mrtext_action_links' );


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
 


add_action('admin_notices', 'mrt_show_upgrade_notice');
function mrt_show_upgrade_notice() {
    // Show only to admins and once per user
    if (!current_user_can('manage_options') || get_user_meta(get_current_user_id(), 'mrt_dismissed_notice', true)) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible mrt-upgrade-notice">
        <p><strong>🚀 Upgrade to Marquee Running Text Pro!</strong><br>
        Unlock unlimited marquees, advanced customization, performance enhancements, and premium support.<br>
        👉 <a href="https://bongdevs.com/wp-assets/marquee-running-text-pro/" target="_blank" style="text-decoration: underline;">Click here to get the Pro version</a></p>
    </div>
    <script>
    jQuery(document).on('click', '.mrt-upgrade-notice .notice-dismiss', function () {
        jQuery.post(ajaxurl, {
            action: 'mrt_dismiss_notice'
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_mrt_dismiss_notice', 'mrt_dismiss_notice');
function mrt_dismiss_notice() {
    update_user_meta(get_current_user_id(), 'mrt_dismissed_notice', true);
    wp_die();
}
?>