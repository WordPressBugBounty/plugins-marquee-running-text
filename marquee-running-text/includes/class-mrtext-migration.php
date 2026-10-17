<?php
/**
 * Upgrades data from version 1.x.
 *
 * Version 1.x stored everything in separate options (five text/link pairs plus
 * style options). On the first load of 2.x those values are copied into one
 * marquee that looks and behaves like the old one. The old options are left in
 * place, so rolling back to 1.x keeps working.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs data migrations once per database version.
 */
class MRText_Migration {

	const DB_VERSION        = 2;
	const VERSION_OPTION    = 'mrtext_db_version';
	const LOCK_OPTION       = 'mrtext_migration_lock';
	const LEGACY_META       = '_mrtext_legacy_v1';
	const NOTICE_OPTION     = 'mrtext_migration_notice';
	const LEGACY_SPEED_STEP = 6;

	/**
	 * Option names used by version 1.x.
	 *
	 * @return string[]
	 */
	public static function legacy_options() {
		$options = array(
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
		return $options;
	}

	/**
	 * Runs pending migrations. Safe to call on every request.
	 */
	public static function maybe_run() {
		if ( (int) get_option( self::VERSION_OPTION, 0 ) >= self::DB_VERSION ) {
			return;
		}

		// add_option() fails when the row exists, so only one request migrates at a time.
		if ( ! add_option( self::LOCK_OPTION, time(), '', false ) ) {
			$started = (int) get_option( self::LOCK_OPTION );
			if ( $started && ( time() - $started ) < 5 * MINUTE_IN_SECONDS ) {
				return;
			}
			update_option( self::LOCK_OPTION, time(), false );
		}

		if ( self::has_legacy_data() ) {
			self::migrate_v1();
		} elseif ( 0 === MRText_Post_Type::count_all() ) {
			self::create_sample();
		}

		update_option( self::VERSION_OPTION, self::DB_VERSION );
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * Whether any 1.x option exists.
	 *
	 * @return bool
	 */
	public static function has_legacy_data() {
		foreach ( self::legacy_options() as $option ) {
			if ( false !== get_option( $option, false ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Converts the 1.x options into one marquee.
	 */
	private static function migrate_v1() {
		$existing = get_posts(
			array(
				'post_type'   => MRText_Post_Type::POST_TYPE,
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => self::LEGACY_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-time lookup during migration.
			)
		);
		if ( $existing ) {
			return;
		}

		$id = MRText_Post_Type::save(
			0,
			__( 'Header Marquee', 'marquee-running-text' ),
			self::build_v1_config(),
			true
		);

		if ( is_wp_error( $id ) ) {
			return;
		}

		update_post_meta( $id, self::LEGACY_META, 1 );
		MRText_Settings::update( array( 'default_marquee' => $id ) );
		update_option( self::NOTICE_OPTION, $id, false );
	}

	/**
	 * Builds a 2.x config that reproduces the 1.x output.
	 *
	 * @return array
	 */
	public static function build_v1_config() {
		$config = MRText_Config::defaults();

		$config['items'] = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$text = get_option( 'mrtext_text_field_' . $i, '' );
			if ( ! is_scalar( $text ) ) {
				continue;
			}
			$text = trim( str_replace( array( "\r", "\n", "\t" ), ' ', (string) $text ) );
			if ( '' === $text ) {
				continue;
			}

			$link = get_option( 'mrtext_text_field_' . $i . '_link', '' );
			$link = is_scalar( $link ) ? trim( (string) $link ) : '';
			if ( '#' === $link ) {
				$link = '';
			}

			// 1.x printed the text with esc_html(), so it is stored escaped to look identical.
			$config['items'][] = array(
				'text'     => esc_html( $text ),
				'link'     => $link,
				'new_tab'  => false,
				'nofollow' => false,
			);
		}

		// 1.x always printed its inline CSS with these defaults.
		$text_color = self::legacy_color( 'mrtext_color_option', '#ffffff' );

		$config['style']['bg_color']        = self::legacy_color( 'mrtext_bg_color_option', '#000000' );
		$config['style']['text_color']      = $text_color;
		$config['style']['separator_color'] = $text_color;
		$config['style']['hover_color']     = self::legacy_color( 'mrtext_hover_color_option', '#ffffff' );
		$config['style']['separator']       = 'dot';
		$config['style']['height']          = 40;
		$config['style']['gap']             = 20;

		list( $size, $unit )                = self::legacy_font_size( get_option( 'mrtext_font_size', '16px' ) );
		$config['style']['font_size']       = $size;
		$config['style']['font_unit']       = $unit;
		$config['style']['font_weight']     = self::legacy_font_weight( get_option( 'mrtext_font_weight', '500' ) );
		$config['motion']['direction']      = 'right' === strtolower( trim( (string) get_option( 'mrtext_font_direction', '' ) ) ) ? 'right' : 'left';
		$config['motion']['speed']          = self::legacy_speed( get_option( 'mrtext_font_scroll_delay', '85' ) );
		$config['motion']['pause_on_hover'] = true;

		// "Hide" only hid the header bar in 1.x; the [mrtext] shortcode kept working.
		$visible                        = 'show' === get_option( 'mrtext_radio', 'show' );
		$config['display']['placement'] = $visible ? 'top' : 'none';
		$config['display']['show_on']   = 'all';

		return $config;
	}

	/**
	 * Reads a 1.x color option.
	 *
	 * @param string $option  Option name.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private static function legacy_color( $option, $fallback ) {
		$value = get_option( $option, $fallback );
		$color = is_string( $value ) ? sanitize_hex_color( trim( $value ) ) : null;
		return $color ? $color : $fallback;
	}

	/**
	 * Parses a 1.x font size such as "16px", "1.2em" or "18".
	 *
	 * @param mixed $value Stored value.
	 * @return array Size and unit.
	 */
	public static function legacy_font_size( $value ) {
		if ( is_scalar( $value ) && preg_match( '/^\s*(\d+(?:\.\d+)?)\s*(px|em|rem)?\s*;?\s*$/i', (string) $value, $m ) ) {
			$unit = isset( $m[2] ) && '' !== $m[2] ? strtolower( $m[2] ) : 'px';
			$size = (float) $m[1];
			if ( $size > 0 ) {
				return array( $size, $unit );
			}
		}
		return array( 16, 'px' );
	}

	/**
	 * Parses a 1.x font weight such as "500" or "bold".
	 *
	 * @param mixed $value Stored value.
	 * @return int
	 */
	public static function legacy_font_weight( $value ) {
		$value    = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		$keywords = array(
			'normal'  => 400,
			'bold'    => 700,
			'bolder'  => 800,
			'lighter' => 300,
		);
		if ( isset( $keywords[ $value ] ) ) {
			return $keywords[ $value ];
		}
		if ( is_numeric( $value ) ) {
			return (int) min( 900, max( 300, round( (float) $value / 100 ) * 100 ) );
		}
		return 500;
	}

	/**
	 * Converts a 1.x scroll delay (ms per 6px step) into pixels per second.
	 * Browsers treat delays under 60ms as 60ms.
	 *
	 * @param mixed $value Stored delay.
	 * @return int
	 */
	public static function legacy_speed( $value ) {
		$delay = is_numeric( $value ) ? (float) $value : 85;
		$delay = max( 60, $delay );
		return (int) min( 500, max( 5, round( self::LEGACY_SPEED_STEP * 1000 / $delay ) ) );
	}

	/**
	 * Creates an inactive example marquee on fresh installs.
	 */
	private static function create_sample() {
		$config          = MRText_Config::defaults();
		$config['items'] = array(
			array( 'text' => __( 'Welcome to our website!', 'marquee-running-text' ) ),
			array( 'text' => __( 'This is your first marquee — edit it under Marquee in the admin menu.', 'marquee-running-text' ) ),
			array( 'text' => __( 'Add links, colors, a label and more.', 'marquee-running-text' ) ),
		);

		$id = MRText_Post_Type::save( 0, __( 'My First Marquee', 'marquee-running-text' ), $config, false );
		if ( ! is_wp_error( $id ) ) {
			MRText_Settings::update( array( 'default_marquee' => $id ) );
		}
	}
}
