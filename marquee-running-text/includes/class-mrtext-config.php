<?php
/**
 * Marquee configuration: defaults and sanitization.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes the shape of a marquee config and keeps it clean.
 */
class MRText_Config {

	/**
	 * Allowed values for select-type settings.
	 *
	 * @return array
	 */
	public static function choices() {
		return array(
			'font_unit'   => array( 'px', 'em', 'rem' ),
			'font_weight' => array( 300, 400, 500, 600, 700, 800, 900 ),
			'separator'   => array( 'dot', 'line', 'star', 'custom', 'none' ),
			'direction'   => array( 'left', 'right' ),
			'placement'   => array( 'top', 'sticky', 'bottom', 'none' ),
			'show_on'     => array( 'all', 'front_page' ),
		);
	}

	/**
	 * Default config for a new marquee.
	 *
	 * @return array
	 */
	public static function defaults() {
		$defaults = array(
			'items'   => array(),
			'label'   => array(
				'enabled' => false,
				'text'    => '',
			),
			'style'   => array(
				'bg_color'        => '#000000',
				'text_color'      => '#ffffff',
				'hover_color'     => '#c5c4c7',
				'separator_color' => '#ffffff',
				'label_bg'        => '#d63638',
				'label_color'     => '#ffffff',
				'font_size'       => 16,
				'font_unit'       => 'px',
				'font_weight'     => 500,
				'height'          => 40,
				'border_radius'   => 0,
				'gap'             => 20,
				'separator'       => 'dot',
				'separator_char'  => '',
			),
			'motion'  => array(
				'direction'      => 'left',
				'speed'          => 60,
				'pause_on_hover' => true,
				'pause_button'   => false,
			),
			'display' => array(
				'placement'    => 'top',
				'show_on'      => 'all',
				'hide_mobile'  => false,
				'hide_desktop' => false,
				'closable'     => false,
			),
		);

		/**
		 * Filters the default marquee config. Add-ons can register extra keys here.
		 *
		 * @param array $defaults Default config.
		 */
		return apply_filters( 'mrtext_default_config', $defaults );
	}

	/**
	 * HTML tags allowed inside an item's text.
	 *
	 * @return array
	 */
	public static function allowed_text_html() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'u'      => array(),
		);
	}

	/**
	 * Sanitizes a raw (unslashed) config array. Missing keys fall back to defaults.
	 *
	 * @param mixed $raw Raw config.
	 * @return array
	 */
	public static function sanitize( $raw ) {
		$raw      = is_array( $raw ) ? $raw : array();
		$defaults = self::defaults();
		$choices  = self::choices();
		$config   = $defaults;

		$config['items'] = array();
		if ( isset( $raw['items'] ) && is_array( $raw['items'] ) ) {
			foreach ( $raw['items'] as $item ) {
				$item = self::sanitize_item( $item );
				if ( null !== $item ) {
					$config['items'][] = $item;
				}
			}
		}

		$label           = self::group( $raw, 'label' );
		$config['label'] = array(
			'enabled' => self::bool( $label, 'enabled', $defaults['label']['enabled'] ),
			'text'    => isset( $label['text'] ) ? sanitize_text_field( $label['text'] ) : $defaults['label']['text'],
		);

		$style = self::group( $raw, 'style' );
		$ds    = $defaults['style'];
		foreach ( array( 'bg_color', 'text_color', 'hover_color', 'separator_color', 'label_bg', 'label_color' ) as $key ) {
			$config['style'][ $key ] = self::color( $style, $key, $ds[ $key ] );
		}
		$config['style']['font_unit']      = self::choice( $style, 'font_unit', $choices['font_unit'], $ds['font_unit'] );
		$max_font                          = 'px' === $config['style']['font_unit'] ? 200 : 12;
		$config['style']['font_size']      = self::number( $style, 'font_size', 0.5, $max_font, $ds['font_size'] );
		$config['style']['font_weight']    = (int) self::choice( $style, 'font_weight', $choices['font_weight'], $ds['font_weight'] );
		$config['style']['height']         = (int) self::number( $style, 'height', 20, 300, $ds['height'] );
		$config['style']['border_radius']  = (int) self::number( $style, 'border_radius', 0, 100, $ds['border_radius'] );
		$config['style']['gap']            = (int) self::number( $style, 'gap', 0, 300, $ds['gap'] );
		$config['style']['separator']      = self::choice( $style, 'separator', $choices['separator'], $ds['separator'] );
		$config['style']['separator_char'] = isset( $style['separator_char'] ) ? self::short_text( $style['separator_char'], 3 ) : $ds['separator_char'];

		$motion = self::group( $raw, 'motion' );
		$dm     = $defaults['motion'];

		$config['motion'] = array(
			'direction'      => self::choice( $motion, 'direction', $choices['direction'], $dm['direction'] ),
			'speed'          => (int) self::number( $motion, 'speed', 5, 500, $dm['speed'] ),
			'pause_on_hover' => self::bool( $motion, 'pause_on_hover', $dm['pause_on_hover'] ),
			'pause_button'   => self::bool( $motion, 'pause_button', $dm['pause_button'] ),
		);

		$display = self::group( $raw, 'display' );
		$dd      = $defaults['display'];

		$config['display'] = array(
			'placement'    => self::choice( $display, 'placement', $choices['placement'], $dd['placement'] ),
			'show_on'      => self::choice( $display, 'show_on', $choices['show_on'], $dd['show_on'] ),
			'hide_mobile'  => self::bool( $display, 'hide_mobile', $dd['hide_mobile'] ),
			'hide_desktop' => self::bool( $display, 'hide_desktop', $dd['hide_desktop'] ),
			'closable'     => self::bool( $display, 'closable', $dd['closable'] ),
		);

		/**
		 * Filters a sanitized marquee config. Add-ons sanitize their own keys here.
		 *
		 * @param array $config Sanitized config.
		 * @param array $raw    Raw input.
		 */
		return apply_filters( 'mrtext_sanitize_config', $config, $raw );
	}

	/**
	 * Sanitizes one item. Returns null for items without visible text.
	 *
	 * @param mixed $item Raw item.
	 * @return array|null
	 */
	public static function sanitize_item( $item ) {
		if ( ! is_array( $item ) ) {
			return null;
		}

		/**
		 * Sanitizes an item of a custom type (for example an image). Return an array to keep
		 * the item, false to drop it, or null to treat it as a text item.
		 *
		 * @param array|false|null $clean Sanitized item.
		 * @param array            $item  Raw item.
		 */
		$custom = apply_filters( 'mrtext_sanitize_custom_item', null, $item );
		if ( null !== $custom ) {
			return is_array( $custom ) ? $custom : null;
		}

		$text = isset( $item['text'] ) && is_scalar( $item['text'] ) ? (string) $item['text'] : '';
		$text = str_replace( array( "\r", "\n", "\t" ), ' ', $text );
		$text = trim( wp_kses( $text, self::allowed_text_html() ) );

		if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
			return null;
		}

		$link = isset( $item['link'] ) && is_scalar( $item['link'] ) ? trim( (string) $item['link'] ) : '';

		$clean = array(
			'text'     => $text,
			'link'     => '' === $link ? '' : esc_url_raw( $link ),
			'new_tab'  => self::bool( $item, 'new_tab', false ),
			'nofollow' => self::bool( $item, 'nofollow', false ),
		);

		/**
		 * Filters a sanitized item.
		 *
		 * @param array $clean Sanitized item.
		 * @param array $item  Raw item.
		 */
		return apply_filters( 'mrtext_sanitize_item', $clean, $item );
	}

	/**
	 * Returns a sub-array of the raw config, or an empty array.
	 *
	 * @param array  $raw Raw config.
	 * @param string $key Group key.
	 * @return array
	 */
	private static function group( array $raw, $key ) {
		return isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ? $raw[ $key ] : array();
	}

	/**
	 * Reads a boolean.
	 *
	 * @param array  $data    Source.
	 * @param string $key     Key.
	 * @param bool   $fallback Fallback when the key is missing.
	 * @return bool
	 */
	private static function bool( array $data, $key, $fallback ) {
		if ( ! isset( $data[ $key ] ) ) {
			return (bool) $fallback;
		}
		return in_array( $data[ $key ], array( true, 1, '1', 'true', 'on', 'yes' ), true );
	}

	/**
	 * Reads a hex color.
	 *
	 * @param array  $data    Source.
	 * @param string $key     Key.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private static function color( array $data, $key, $fallback ) {
		if ( ! isset( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
			return $fallback;
		}
		$color = sanitize_hex_color( trim( $data[ $key ] ) );
		return $color ? $color : $fallback;
	}

	/**
	 * Reads a number clamped to a range.
	 *
	 * @param array     $data    Source.
	 * @param string    $key     Key.
	 * @param int|float $min     Minimum.
	 * @param int|float $max     Maximum.
	 * @param int|float $fallback Fallback.
	 * @return float
	 */
	private static function number( array $data, $key, $min, $max, $fallback ) {
		if ( ! isset( $data[ $key ] ) || ! is_numeric( $data[ $key ] ) ) {
			return (float) $fallback;
		}
		return (float) min( $max, max( $min, round( (float) $data[ $key ], 2 ) ) );
	}

	/**
	 * Reads a value restricted to a list of choices.
	 *
	 * @param array  $data    Source.
	 * @param string $key     Key.
	 * @param array  $allowed Allowed values.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	private static function choice( array $data, $key, array $allowed, $fallback ) {
		if ( ! isset( $data[ $key ] ) ) {
			return $fallback;
		}
		foreach ( $allowed as $value ) {
			if ( (string) $value === (string) $data[ $key ] ) {
				return $value;
			}
		}
		return $fallback;
	}

	/**
	 * Plain text limited to a number of characters.
	 *
	 * @param mixed $value  Raw value.
	 * @param int   $length Max characters.
	 * @return string
	 */
	private static function short_text( $value, $length ) {
		$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}
}
