<?php
/**
 * Marquee editor screen (add / edit) with live preview.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the editor and saves its form.
 */
class MRText_Admin_Editor {

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'admin_post_mrtext_save', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Saves the editor form.
	 */
	public static function handle_save() {
		if ( ! current_user_can( MRText_Admin::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'marquee-running-text' ), 403 );
		}
		check_admin_referer( 'mrtext_save_marquee', 'mrtext_nonce' );

		$id = isset( $_POST['mrtext_id'] ) ? absint( $_POST['mrtext_id'] ) : 0;
		if ( $id && ! MRText_Post_Type::get( $id ) ) {
			MRText_Admin::redirect( 'error' );
		}
		if ( ! $id && ! MRText_Post_Type::can_create() ) {
			MRText_Admin::redirect( 'limit' );
		}

		$title  = isset( $_POST['mrtext_title'] ) ? sanitize_text_field( wp_unslash( $_POST['mrtext_title'] ) ) : '';
		$active = ! empty( $_POST['mrtext_active'] );
		// Sanitized field by field in MRText_Config::sanitize().
		$config = isset( $_POST['mrtext'] ) && is_array( $_POST['mrtext'] ) ? wp_unslash( $_POST['mrtext'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( '' === $title ) {
			$title = __( 'Untitled Marquee', 'marquee-running-text' );
		}

		$saved = MRText_Post_Type::save( $id, $title, $config, $active );
		if ( is_wp_error( $saved ) ) {
			MRText_Admin::redirect( 'error' );
		}

		wp_safe_redirect(
			MRText_Admin::url(
				MRText_Admin::EDIT_SLUG,
				array(
					'id'         => $saved,
					'mrtext_msg' => 'saved',
				)
			)
		);
		exit;
	}

	/**
	 * Renders the editor screen.
	 */
	public static function render() {
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen.
		$post = $id ? MRText_Post_Type::get( $id ) : null;

		if ( $id && ! $post ) {
			wp_die( esc_html__( 'This marquee does not exist.', 'marquee-running-text' ) );
		}

		if ( ! $post && ! MRText_Post_Type::can_create() ) {
			self::render_limit_reached();
			return;
		}

		$config = $post ? MRText_Post_Type::get_config( $post->ID ) : MRText_Config::defaults();
		$title  = $post ? $post->post_title : '';
		$active = $post ? 'publish' === $post->post_status : true;

		if ( ! $post ) {
			$config['items'] = array( array( 'text' => '' ) );
		}
		?>
		<div class="wrap mrtext-admin">
			<h1 class="wp-heading-inline">
				<?php echo $post ? esc_html__( 'Edit Marquee', 'marquee-running-text' ) : esc_html__( 'Add New Marquee', 'marquee-running-text' ); ?>
			</h1>
			<a href="<?php echo esc_url( MRText_Admin::url() ); ?>" class="page-title-action"><?php esc_html_e( 'All Marquees', 'marquee-running-text' ); ?></a>
			<hr class="wp-header-end">

			<?php MRText_Admin::print_message(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mrtext-editor" class="mrtext-editor">
				<input type="hidden" name="action" value="mrtext_save">
				<input type="hidden" name="mrtext_id" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'mrtext_save_marquee', 'mrtext_nonce' ); ?>

				<div class="mrtext-layout">
					<div class="mrtext-layout__main">
						<div class="mrtext-card mrtext-title-card">
							<label class="screen-reader-text" for="mrtext-title"><?php esc_html_e( 'Marquee title', 'marquee-running-text' ); ?></label>
							<input type="text" id="mrtext-title" name="mrtext_title" class="mrtext-title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Marquee title (only you see this)', 'marquee-running-text' ); ?>">
						</div>

						<div class="mrtext-card mrtext-preview-card">
							<h2 class="mrtext-card__title"><?php esc_html_e( 'Live preview', 'marquee-running-text' ); ?></h2>
							<div id="mrtext-preview" class="mrtext-preview" aria-live="off"></div>
						</div>

						<div class="mrtext-card mrtext-tabs-card">
							<nav class="mrtext-tabs" role="tablist">
								<?php
								$tabs = array(
									'content' => __( 'Content', 'marquee-running-text' ),
									'style'   => __( 'Style', 'marquee-running-text' ),
									'motion'  => __( 'Motion', 'marquee-running-text' ),
									'display' => __( 'Display', 'marquee-running-text' ),
								);
								/**
								 * Filters the editor tabs. Add-ons add their own tab and print it on "mrtext_editor_panel_{tab}".
								 *
								 * @param array $tabs Tab key => label.
								 */
								$tabs  = apply_filters( 'mrtext_editor_tabs', $tabs );
								$first = true;
								foreach ( $tabs as $key => $label ) :
									?>
									<button type="button" role="tab" class="mrtext-tabs__tab<?php echo $first ? ' is-active' : ''; ?>" id="mrtext-tab-<?php echo esc_attr( $key ); ?>" aria-controls="mrtext-panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
									<?php
									$first = false;
								endforeach;
								?>
							</nav>

							<?php
							$first = true;
							foreach ( array_keys( $tabs ) as $key ) :
								?>
								<div class="mrtext-panel" role="tabpanel" id="mrtext-panel-<?php echo esc_attr( $key ); ?>" aria-labelledby="mrtext-tab-<?php echo esc_attr( $key ); ?>"<?php echo $first ? '' : ' hidden'; ?>>
									<?php
									$method = 'panel_' . $key;
									if ( method_exists( __CLASS__, $method ) ) {
										self::$method( $config );
									}
									/**
									 * Prints extra fields in an editor tab.
									 *
									 * @param array        $config Marquee config.
									 * @param WP_Post|null $post   Marquee post.
									 */
									do_action( 'mrtext_editor_panel_' . $key, $config, $post );
									?>
								</div>
								<?php
								$first = false;
							endforeach;
							?>
						</div>
					</div>

					<div class="mrtext-layout__side">
						<div class="mrtext-card mrtext-publish">
							<h2 class="mrtext-card__title"><?php esc_html_e( 'Publish', 'marquee-running-text' ); ?></h2>
							<label class="mrtext-switch">
								<input type="checkbox" name="mrtext_active" value="1" <?php checked( $active ); ?>>
								<span class="mrtext-switch__ui" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Active', 'marquee-running-text' ); ?></span>
							</label>
							<p class="description"><?php esc_html_e( 'Inactive marquees are not shown anywhere on the site.', 'marquee-running-text' ); ?></p>

							<?php if ( $post ) : ?>
								<p class="mrtext-publish__label"><?php esc_html_e( 'Shortcode', 'marquee-running-text' ); ?></p>
								<span class="mrtext-copy">
									<input type="text" readonly value="<?php echo esc_attr( '[mrtext id="' . $post->ID . '"]' ); ?>" aria-label="<?php esc_attr_e( 'Shortcode', 'marquee-running-text' ); ?>">
									<button type="button" class="button mrtext-copy__btn"><?php esc_html_e( 'Copy', 'marquee-running-text' ); ?></button>
								</span>
							<?php endif; ?>

							<div class="mrtext-publish__actions">
								<?php if ( $post ) : ?>
									<a class="mrtext-delete submitdelete" href="<?php echo esc_url( MRText_Admin::action_url( 'delete', $post->ID ) ); ?>"><?php esc_html_e( 'Delete', 'marquee-running-text' ); ?></a>
								<?php endif; ?>
								<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Marquee', 'marquee-running-text' ); ?></button>
							</div>
						</div>
						<?php MRText_Admin::render_side_cards(); ?>
					</div>
				</div>
			</form>

			<template id="tmpl-mrtext-item">
				<?php self::item_row( '__INDEX__', array() ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * Screen shown when the marquee limit is reached.
	 */
	private static function render_limit_reached() {
		?>
		<div class="wrap mrtext-admin">
			<h1><?php esc_html_e( 'Add New Marquee', 'marquee-running-text' ); ?></h1>
			<div class="mrtext-card mrtext-limit">
				<p>
					<?php
					/* translators: %d: maximum number of marquees. */
					echo esc_html( sprintf( __( 'The free version includes up to %d marquees. Delete a marquee or upgrade to Pro for unlimited marquees.', 'marquee-running-text' ), MRText_Post_Type::get_limit() ) );
					?>
				</p>
				<a class="button" href="<?php echo esc_url( MRText_Admin::url() ); ?>"><?php esc_html_e( 'Back to marquees', 'marquee-running-text' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( MRText_Admin::pro_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Pro', 'marquee-running-text' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Content tab.
	 *
	 * @param array $config Config.
	 */
	private static function panel_content( array $config ) {
		?>
		<h3 class="mrtext-section"><?php esc_html_e( 'Items', 'marquee-running-text' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Drag to reorder. You can use <strong>, <em> and emoji in the text.', 'marquee-running-text' ); ?></p>
		<ul id="mrtext-items" class="mrtext-items">
			<?php
			foreach ( array_values( $config['items'] ) as $index => $item ) {
				if ( ! empty( $item['type'] ) && 'text' !== $item['type'] ) {
					/**
					 * Prints the editor row of a custom item type.
					 *
					 * @param string $index Row index.
					 * @param array  $item  Item data.
					 */
					do_action( 'mrtext_editor_custom_item_row', (string) $index, $item );
					continue;
				}
				self::item_row( (string) $index, $item );
			}
			?>
		</ul>
		<div class="mrtext-item-actions">
			<button type="button" class="button" id="mrtext-add-item"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Add item', 'marquee-running-text' ); ?></button>
			<?php
			/**
			 * Prints extra "add item" buttons.
			 */
			do_action( 'mrtext_editor_item_buttons' );
			?>
		</div>

		<h3 class="mrtext-section"><?php esc_html_e( 'Label', 'marquee-running-text' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			self::checkbox_row( 'label', 'enabled', __( 'Show label', 'marquee-running-text' ), $config['label']['enabled'], __( 'A fixed box at the start of the bar, e.g. "Breaking News".', 'marquee-running-text' ) );
			self::text_row( 'label', 'text', __( 'Label text', 'marquee-running-text' ), $config['label']['text'], __( 'Breaking News', 'marquee-running-text' ) );
			?>
		</table>
		<?php
	}

	/**
	 * Style tab.
	 *
	 * @param array $config Config.
	 */
	private static function panel_style( array $config ) {
		$style = $config['style'];
		?>
		<h3 class="mrtext-section"><?php esc_html_e( 'Colors', 'marquee-running-text' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			self::color_row( 'bg_color', __( 'Background', 'marquee-running-text' ), $style['bg_color'] );
			self::color_row( 'text_color', __( 'Text', 'marquee-running-text' ), $style['text_color'] );
			self::color_row( 'hover_color', __( 'Text on hover', 'marquee-running-text' ), $style['hover_color'] );
			self::color_row( 'separator_color', __( 'Separator', 'marquee-running-text' ), $style['separator_color'] );
			self::color_row( 'label_bg', __( 'Label background', 'marquee-running-text' ), $style['label_bg'] );
			self::color_row( 'label_color', __( 'Label text', 'marquee-running-text' ), $style['label_color'] );
			?>
		</table>

		<h3 class="mrtext-section"><?php esc_html_e( 'Text & size', 'marquee-running-text' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="mrtext-style-font_size"><?php esc_html_e( 'Font size', 'marquee-running-text' ); ?></label></th>
				<td>
					<input type="number" id="mrtext-style-font_size" name="mrtext[style][font_size]" value="<?php echo esc_attr( $style['font_size'] ); ?>" min="0.5" max="200" step="0.1" class="small-text">
					<label class="screen-reader-text" for="mrtext-style-font_unit"><?php esc_html_e( 'Font size unit', 'marquee-running-text' ); ?></label>
					<select id="mrtext-style-font_unit" name="mrtext[style][font_unit]">
						<?php foreach ( MRText_Config::choices()['font_unit'] as $unit ) : ?>
							<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $style['font_unit'], $unit ); ?>><?php echo esc_html( $unit ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php
			$weights = array(
				300 => __( 'Light (300)', 'marquee-running-text' ),
				400 => __( 'Normal (400)', 'marquee-running-text' ),
				500 => __( 'Medium (500)', 'marquee-running-text' ),
				600 => __( 'Semi-bold (600)', 'marquee-running-text' ),
				700 => __( 'Bold (700)', 'marquee-running-text' ),
				800 => __( 'Extra-bold (800)', 'marquee-running-text' ),
				900 => __( 'Black (900)', 'marquee-running-text' ),
			);
			self::select_row( 'style', 'font_weight', __( 'Font weight', 'marquee-running-text' ), $style['font_weight'], $weights );
			self::number_row( 'style', 'height', __( 'Bar height', 'marquee-running-text' ), $style['height'], 20, 300, 'px' );
			self::number_row( 'style', 'gap', __( 'Space between items', 'marquee-running-text' ), $style['gap'], 0, 300, 'px' );
			self::number_row( 'style', 'border_radius', __( 'Corner radius', 'marquee-running-text' ), $style['border_radius'], 0, 100, 'px' );
			?>
		</table>

		<h3 class="mrtext-section"><?php esc_html_e( 'Separator', 'marquee-running-text' ); ?></h3>
		<table class="form-table" role="presentation">
			<?php
			self::select_row(
				'style',
				'separator',
				__( 'Separator', 'marquee-running-text' ),
				$style['separator'],
				array(
					'dot'    => __( 'Dot', 'marquee-running-text' ),
					'line'   => __( 'Line', 'marquee-running-text' ),
					'star'   => __( 'Star', 'marquee-running-text' ),
					'custom' => __( 'Custom character or emoji', 'marquee-running-text' ),
					'none'   => __( 'None', 'marquee-running-text' ),
				)
			);
			self::text_row( 'style', 'separator_char', __( 'Custom separator', 'marquee-running-text' ), $style['separator_char'], '✦', 'mrtext-row-separator-char' );
			?>
		</table>
		<?php
	}

	/**
	 * Motion tab.
	 *
	 * @param array $config Config.
	 */
	private static function panel_motion( array $config ) {
		$motion = $config['motion'];
		?>
		<table class="form-table" role="presentation">
			<?php
			self::select_row(
				'motion',
				'direction',
				__( 'Direction', 'marquee-running-text' ),
				$motion['direction'],
				array(
					'left'  => __( 'Right to left ←', 'marquee-running-text' ),
					'right' => __( 'Left to right →', 'marquee-running-text' ),
				)
			);
			?>
			<tr>
				<th scope="row"><label for="mrtext-motion-speed"><?php esc_html_e( 'Speed', 'marquee-running-text' ); ?></label></th>
				<td>
					<input type="range" id="mrtext-motion-speed" name="mrtext[motion][speed]" value="<?php echo esc_attr( $motion['speed'] ); ?>" min="5" max="300" step="1" class="mrtext-range">
					<output for="mrtext-motion-speed" class="mrtext-range-value"><?php echo esc_html( $motion['speed'] ); ?></output>
					<span class="description"><?php esc_html_e( 'pixels per second', 'marquee-running-text' ); ?></span>
				</td>
			</tr>
			<?php
			self::checkbox_row( 'motion', 'pause_on_hover', __( 'Pause on hover', 'marquee-running-text' ), $motion['pause_on_hover'] );
			self::checkbox_row( 'motion', 'pause_button', __( 'Pause / play button', 'marquee-running-text' ), $motion['pause_button'], __( 'Recommended for accessibility (WCAG 2.2.2).', 'marquee-running-text' ) );
			?>
		</table>
		<p class="description"><?php esc_html_e( 'Visitors who turned on "reduce motion" in their system settings see a still bar they can scroll by hand.', 'marquee-running-text' ); ?></p>
		<?php
	}

	/**
	 * Display tab.
	 *
	 * @param array $config Config.
	 */
	private static function panel_display( array $config ) {
		$display = $config['display'];
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Placement', 'marquee-running-text' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Placement', 'marquee-running-text' ); ?></legend>
						<?php foreach ( MRText_Config::choices()['placement'] as $placement ) : ?>
							<label class="mrtext-radio">
								<input type="radio" name="mrtext[display][placement]" value="<?php echo esc_attr( $placement ); ?>" <?php checked( $display['placement'], $placement ); ?>>
								<?php echo esc_html( MRText_Admin::placement_label( $placement ) ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'The shortcode, block and Elementor widget work with every placement.', 'marquee-running-text' ); ?></p>
				</td>
			</tr>
			<?php
			self::select_row(
				'display',
				'show_on',
				__( 'Show on', 'marquee-running-text' ),
				$display['show_on'],
				array(
					'all'        => __( 'Entire site', 'marquee-running-text' ),
					'front_page' => __( 'Homepage only', 'marquee-running-text' ),
				),
				__( 'Applies to the top, sticky and bottom placements.', 'marquee-running-text' )
			);
			self::checkbox_row( 'display', 'hide_mobile', __( 'Hide on mobile', 'marquee-running-text' ), $display['hide_mobile'], __( 'Screens narrower than 768px.', 'marquee-running-text' ) );
			self::checkbox_row( 'display', 'hide_desktop', __( 'Hide on desktop', 'marquee-running-text' ), $display['hide_desktop'] );
			self::checkbox_row( 'display', 'closable', __( 'Close button', 'marquee-running-text' ), $display['closable'], __( 'Visitors can close the bar. It comes back after you edit the marquee.', 'marquee-running-text' ) );
			?>
		</table>
		<?php if ( ! MRText_Admin::is_pro_active() ) : ?>
			<p class="mrtext-pro-hint">
				<span class="dashicons dashicons-lock" aria-hidden="true"></span>
				<?php esc_html_e( 'Need page-by-page rules, user roles or start/end dates?', 'marquee-running-text' ); ?>
				<a href="<?php echo esc_url( MRText_Admin::pro_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Available in Pro', 'marquee-running-text' ); ?></a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * One item row of the repeater.
	 *
	 * @param string $index Row index, or "__INDEX__" for the JS template.
	 * @param array  $item  Item data.
	 */
	private static function item_row( $index, array $item ) {
		$item = wp_parse_args(
			$item,
			array(
				'text'     => '',
				'link'     => '',
				'new_tab'  => false,
				'nofollow' => false,
			)
		);
		$base = 'mrtext[items][' . $index . ']';
		?>
		<li class="mrtext-item">
			<span class="mrtext-item__handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'marquee-running-text' ); ?>" aria-hidden="true"></span>
			<div class="mrtext-item__fields">
				<input type="text" class="mrtext-item__text" name="<?php echo esc_attr( $base ); ?>[text]" value="<?php echo esc_attr( $item['text'] ); ?>" placeholder="<?php esc_attr_e( 'Announcement text', 'marquee-running-text' ); ?>" aria-label="<?php esc_attr_e( 'Text', 'marquee-running-text' ); ?>">
				<div class="mrtext-item__row">
					<input type="text" class="mrtext-item__link" name="<?php echo esc_attr( $base ); ?>[link]" value="<?php echo esc_attr( $item['link'] ); ?>" placeholder="<?php esc_attr_e( 'Link (optional), e.g. https://example.com', 'marquee-running-text' ); ?>" aria-label="<?php esc_attr_e( 'Link', 'marquee-running-text' ); ?>">
					<label><input type="hidden" name="<?php echo esc_attr( $base ); ?>[new_tab]" value="0"><input type="checkbox" class="mrtext-item__newtab" name="<?php echo esc_attr( $base ); ?>[new_tab]" value="1" <?php checked( $item['new_tab'] ); ?>> <?php esc_html_e( 'New tab', 'marquee-running-text' ); ?></label>
					<label><input type="hidden" name="<?php echo esc_attr( $base ); ?>[nofollow]" value="0"><input type="checkbox" class="mrtext-item__nofollow" name="<?php echo esc_attr( $base ); ?>[nofollow]" value="1" <?php checked( $item['nofollow'] ); ?>> <?php esc_html_e( 'nofollow', 'marquee-running-text' ); ?></label>
					<?php
					/**
					 * Prints extra fields in a text item row.
					 *
					 * @param string $index Row index ("__INDEX__" in the JS template).
					 * @param array  $item  Item data.
					 * @param string $base  Field name prefix, e.g. mrtext[items][0].
					 */
					do_action( 'mrtext_editor_item_fields', $index, $item, $base );
					?>
				</div>
			</div>
			<button type="button" class="mrtext-item__remove button-link" aria-label="<?php esc_attr_e( 'Remove item', 'marquee-running-text' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
		</li>
		<?php
	}

	/**
	 * Color picker row.
	 *
	 * @param string $key   Style key.
	 * @param string $label Label.
	 * @param string $value Value.
	 */
	private static function color_row( $key, $label, $value ) {
		$id = 'mrtext-style-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input type="text" id="<?php echo esc_attr( $id ); ?>" class="mrtext-color" name="mrtext[style][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( MRText_Config::defaults()['style'][ $key ] ); ?>"></td>
		</tr>
		<?php
	}

	/**
	 * Number row.
	 *
	 * @param string    $group Config group.
	 * @param string    $key   Key.
	 * @param string    $label Label.
	 * @param int|float $value Value.
	 * @param int       $min   Minimum.
	 * @param int       $max   Maximum.
	 * @param string    $unit  Unit shown after the field.
	 */
	private static function number_row( $group, $key, $label, $value, $min, $max, $unit ) {
		$id = 'mrtext-' . $group . '-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" id="<?php echo esc_attr( $id ); ?>" name="mrtext[<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" class="small-text">
				<?php echo esc_html( $unit ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Text row.
	 *
	 * @param string $group       Config group.
	 * @param string $key         Key.
	 * @param string $label       Label.
	 * @param string $value       Value.
	 * @param string $placeholder Placeholder.
	 * @param string $row_class   Row CSS class.
	 */
	private static function text_row( $group, $key, $label, $value, $placeholder = '', $row_class = '' ) {
		$id = 'mrtext-' . $group . '-' . $key;
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input type="text" id="<?php echo esc_attr( $id ); ?>" name="mrtext[<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="regular-text"></td>
		</tr>
		<?php
	}

	/**
	 * Select row.
	 *
	 * @param string $group       Config group.
	 * @param string $key         Key.
	 * @param string $label       Label.
	 * @param mixed  $value       Selected value.
	 * @param array  $options     Value => label.
	 * @param string $description Help text.
	 */
	private static function select_row( $group, $key, $label, $value, array $options, $description = '' ) {
		$id = 'mrtext-' . $group . '-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="<?php echo esc_attr( $id ); ?>" name="mrtext[<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $key ); ?>]">
					<?php foreach ( $options as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( (string) $value, (string) $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Checkbox row. A hidden "0" makes unchecked boxes save as false.
	 *
	 * @param string $group       Config group.
	 * @param string $key         Key.
	 * @param string $label       Label.
	 * @param bool   $checked     Checked.
	 * @param string $description Help text.
	 */
	private static function checkbox_row( $group, $key, $label, $checked, $description = '' ) {
		$id   = 'mrtext-' . $group . '-' . $key;
		$name = 'mrtext[' . $group . '][' . $key . ']';
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0">
				<label for="<?php echo esc_attr( $id ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>>
					<?php esc_html_e( 'Enable', 'marquee-running-text' ); ?>
				</label>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
