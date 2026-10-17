<?php
/**
 * Global plugin settings.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores the plugin-wide settings in a single option.
 */
class MRText_Settings {

	const OPTION = 'mrtext_settings';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'default_marquee' => 0,
			'delete_data'     => false,
		);
	}

	/**
	 * Returns all settings, or one setting when a key is given.
	 *
	 * @param string|null $key Setting key.
	 * @return mixed
	 */
	public static function get( $key = null ) {
		$settings = get_option( self::OPTION, array() );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );

		if ( null === $key ) {
			return $settings;
		}
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Merges values into the stored settings.
	 *
	 * @param array $values Values to store.
	 */
	public static function update( array $values ) {
		update_option( self::OPTION, self::sanitize( array_merge( self::get(), $values ) ) );
	}

	/**
	 * Sanitizes the settings array.
	 *
	 * @param mixed $raw Raw settings.
	 * @return array
	 */
	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();

		return array(
			'default_marquee' => isset( $raw['default_marquee'] ) ? absint( $raw['default_marquee'] ) : 0,
			'delete_data'     => ! empty( $raw['delete_data'] ),
		);
	}
}
