<?php
/**
 * Builds marquee HTML. Every output path (header, footer, shortcode, block,
 * Elementor) goes through this class.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders a marquee config to escaped HTML.
 */
class MRText_Renderer {

	/**
	 * Renders a stored marquee.
	 *
	 * @param int   $id   Marquee post ID.
	 * @param array $args Render arguments, see render_config().
	 * @return string Escaped HTML.
	 */
	public static function render( $id, array $args = array() ) {
		$post = MRText_Post_Type::get( $id );
		if ( ! $post ) {
			return '';
		}

		$args = array_merge(
			array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'version' => (string) strtotime( $post->post_modified_gmt ),
			),
			$args
		);

		return self::render_config( MRText_Post_Type::get_config( $post->ID ), $args );
	}

	/**
	 * Renders a config.
	 *
	 * @param array $config Sanitized config.
	 * @param array $args {
	 *     Render arguments.
	 *
	 *     @type int   $id       Marquee ID.
	 *     @type string $title    Marquee title, used as accessible name.
	 *     @type string $version  Changes when the marquee is edited; resets "closed" state.
	 *     @type string $position inline|top|sticky|bottom.
	 *     @type bool   $relocate Move to the top of <body> with JS (themes without wp_body_open).
	 * }
	 * @return string Escaped HTML.
	 */
	public static function render_config( array $config, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'       => 0,
				'title'    => '',
				'version'  => '',
				'position' => 'inline',
				'relocate' => false,
			)
		);

		/**
		 * Filters the config right before rendering.
		 *
		 * @param array $config Config.
		 * @param array $args   Render arguments.
		 */
		$config = apply_filters( 'mrtext_render_config', $config, $args );

		/**
		 * Filters whether the marquee renders at all (every placement, shortcode, block and widget).
		 *
		 * @param bool  $visible Whether to render.
		 * @param array $config  Config.
		 * @param array $args    Render arguments.
		 */
		if ( ! apply_filters( 'mrtext_is_visible', true, $config, $args ) ) {
			return '';
		}

		/**
		 * Filters the separator HTML printed before each item. Must return escaped HTML.
		 *
		 * @param string $separator Separator HTML.
		 * @param array  $config    Config.
		 */
		$separator = apply_filters( 'mrtext_separator_html', self::separator_html( $config['style'] ), $config );

		$items_html = '';
		foreach ( $config['items'] as $item ) {
			/**
			 * Filters the inner HTML of one item. Must return escaped HTML.
			 *
			 * @param string $html   Item HTML.
			 * @param array  $item   Item data.
			 * @param array  $config Marquee config.
			 * @param array  $args   Render arguments.
			 */
			$html = apply_filters( 'mrtext_item_html', self::item_html( $item ), $item, $config, $args );
			if ( '' !== $html ) {
				$items_html .= '<li class="mrtext__item text-container">' . $separator . $html . '</li>';
			}
		}

		if ( '' === $items_html ) {
			return '';
		}

		self::enqueue_assets();

		$style   = $config['style'];
		$motion  = $config['motion'];
		$display = $config['display'];
		$label   = $config['label']['enabled'] ? trim( $config['label']['text'] ) : '';

		$classes = array( 'mrtext', 'runtext-container', 'mrtext--' . sanitize_html_class( $args['position'] ) );
		if ( $display['hide_mobile'] ) {
			$classes[] = 'mrtext--hide-mobile';
		}
		if ( $display['hide_desktop'] ) {
			$classes[] = 'mrtext--hide-desktop';
		}
		if ( $args['relocate'] ) {
			$classes[] = 'mrtext--relocate';
		}

		$aria_label = '' !== $label ? $label : ( '' !== $args['title'] ? $args['title'] : __( 'Announcements', 'marquee-running-text' ) );

		$attributes = array(
			'id'                  => 'mrtext-' . absint( $args['id'] ),
			'class'               => implode( ' ', $classes ),
			'role'                => 'marquee',
			'aria-label'          => $aria_label,
			'style'               => self::css_vars( $style ),
			'data-speed'          => (string) (int) $motion['speed'],
			'data-direction'      => $motion['direction'],
			'data-pause-on-hover' => $motion['pause_on_hover'] ? '1' : '0',
			'data-closable'       => $display['closable'] ? '1' : '0',
			'data-key'            => absint( $args['id'] ) . '-' . $args['version'],
		);

		/**
		 * Filters the wrapper attributes. Values are escaped with esc_attr() when printed.
		 *
		 * @param array $attributes Attribute name => value.
		 * @param array $config     Config.
		 * @param array $args       Render arguments.
		 */
		$attributes = apply_filters( 'mrtext_wrapper_attributes', $attributes, $config, $args );

		$html = '<div';
		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		$html .= '>';

		if ( '' !== $label ) {
			$html .= '<span class="mrtext__label">' . esc_html( $label ) . '</span>';
		}

		$html .= '<div class="mrtext__viewport"><div class="mrtext__track"><ul class="mrtext__group">' . $items_html . '</ul></div></div>';

		if ( $motion['pause_button'] ) {
			$html .= '<button type="button" class="mrtext__btn mrtext__pause" aria-pressed="false" aria-label="' . esc_attr__( 'Pause', 'marquee-running-text' ) . '" data-label-pause="' . esc_attr__( 'Pause', 'marquee-running-text' ) . '" data-label-play="' . esc_attr__( 'Play', 'marquee-running-text' ) . '"><span class="mrtext__icon" aria-hidden="true"></span></button>';
		}
		if ( $display['closable'] ) {
			$html .= '<button type="button" class="mrtext__btn mrtext__close" aria-label="' . esc_attr__( 'Close', 'marquee-running-text' ) . '"><span aria-hidden="true">&times;</span></button>';
		}

		$html .= '</div>';

		/**
		 * Filters the final marquee HTML.
		 *
		 * @param string $html   HTML.
		 * @param array  $config Config.
		 * @param array  $args   Render arguments.
		 */
		return apply_filters( 'mrtext_marquee_html', $html, $config, $args );
	}

	/**
	 * Enqueues the front-end assets (safe to call late; WordPress prints them in the footer).
	 */
	public static function enqueue_assets() {
		wp_enqueue_style( 'mrtext' );
		wp_enqueue_script( 'mrtext' );
	}

	/**
	 * HTML for one text item.
	 *
	 * @param array $item Item data.
	 * @return string
	 */
	private static function item_html( array $item ) {
		$text = isset( $item['text'] ) ? wp_kses( $item['text'], MRText_Config::allowed_text_html() ) : '';
		if ( '' === $text ) {
			return '';
		}

		/**
		 * Filters the escaped inner HTML of a text item (for example to add an icon).
		 *
		 * @param string $text Escaped HTML.
		 * @param array  $item Item data.
		 */
		$text = apply_filters( 'mrtext_item_inner_html', $text, $item );

		$link = isset( $item['link'] ) ? $item['link'] : '';
		if ( '' === $link || '#' === $link ) {
			return '<span class="mrtext__link">' . $text . '</span>';
		}

		$rel    = array();
		$target = '';
		if ( ! empty( $item['new_tab'] ) ) {
			$target = ' target="_blank"';
			$rel[]  = 'noopener';
		}
		if ( ! empty( $item['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}
		$rel_attr = $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '';

		return '<a class="mrtext__link" href="' . esc_url( $link ) . '"' . $target . $rel_attr . '>' . $text . '</a>';
	}

	/**
	 * HTML for the separator placed before each item.
	 *
	 * @param array $style Style config.
	 * @return string
	 */
	private static function separator_html( array $style ) {
		switch ( $style['separator'] ) {
			case 'none':
				return '';
			case 'star':
				$char = "\u{2605}";
				break;
			case 'custom':
				$char = $style['separator_char'];
				if ( '' === $char ) {
					return '';
				}
				break;
			default:
				return '<span class="mrtext__sep mrtext__sep--' . esc_attr( $style['separator'] ) . '" aria-hidden="true"></span>';
		}
		return '<span class="mrtext__sep mrtext__sep--char" aria-hidden="true">' . esc_html( $char ) . '</span>';
	}

	/**
	 * CSS custom properties for the wrapper. All values are pre-validated in MRText_Config.
	 *
	 * @param array $style Style config.
	 * @return string
	 */
	private static function css_vars( array $style ) {
		$vars = array(
			'--mrtext-bg'          => $style['bg_color'],
			'--mrtext-color'       => $style['text_color'],
			'--mrtext-hover'       => $style['hover_color'],
			'--mrtext-sep'         => $style['separator_color'],
			'--mrtext-label-bg'    => $style['label_bg'],
			'--mrtext-label-color' => $style['label_color'],
			'--mrtext-font-size'   => self::format_number( $style['font_size'] ) . $style['font_unit'],
			'--mrtext-weight'      => (string) (int) $style['font_weight'],
			'--mrtext-height'      => (int) $style['height'] . 'px',
			'--mrtext-radius'      => (int) $style['border_radius'] . 'px',
			'--mrtext-gap'         => (int) $style['gap'] . 'px',
		);

		/**
		 * Filters the CSS custom properties of the wrapper. Values must be validated by the caller.
		 *
		 * @param array $vars  Property => value.
		 * @param array $style Style config.
		 */
		$vars = apply_filters( 'mrtext_css_vars', $vars, $style );

		$css = '';
		foreach ( $vars as $name => $value ) {
			$css .= $name . ':' . $value . ';';
		}
		return $css;
	}

	/**
	 * Formats a number without trailing zeros.
	 *
	 * @param float $number Number.
	 * @return string
	 */
	private static function format_number( $number ) {
		return rtrim( rtrim( number_format( (float) $number, 2, '.', '' ), '0' ), '.' );
	}
}
