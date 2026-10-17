<?php
/**
 * Admin menu, marquee list, settings page and form handlers.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin screens except the marquee editor.
 */
class MRText_Admin {

	const CAP       = 'manage_options';
	const LIST_SLUG = 'mrtext-settings';
	const EDIT_SLUG = 'mrtext-edit';
	const OPT_SLUG  = 'mrtext-options';

	/**
	 * Page hook suffixes of the plugin screens.
	 *
	 * @var string[]
	 */
	private static $hooks = array();

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) );
		add_action( 'admin_post_mrtext_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_mrtext_duplicate', array( __CLASS__, 'handle_duplicate' ) );
		add_action( 'admin_post_mrtext_toggle', array( __CLASS__, 'handle_toggle' ) );
		add_action( 'admin_post_mrtext_dismiss_notice', array( __CLASS__, 'handle_dismiss_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MRTEXT_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Registers the admin menu.
	 */
	public static function menu() {
		self::$hooks[] = add_menu_page(
			__( 'Marquee Running Text', 'marquee-running-text' ),
			__( 'Marquee', 'marquee-running-text' ),
			self::CAP,
			self::LIST_SLUG,
			array( __CLASS__, 'render_list' ),
			'dashicons-megaphone',
			100
		);
		self::$hooks[] = add_submenu_page(
			self::LIST_SLUG,
			__( 'All Marquees', 'marquee-running-text' ),
			__( 'All Marquees', 'marquee-running-text' ),
			self::CAP,
			self::LIST_SLUG,
			array( __CLASS__, 'render_list' )
		);
		self::$hooks[] = add_submenu_page(
			self::LIST_SLUG,
			__( 'Add New Marquee', 'marquee-running-text' ),
			__( 'Add New', 'marquee-running-text' ),
			self::CAP,
			self::EDIT_SLUG,
			array( 'MRText_Admin_Editor', 'render' )
		);
		self::$hooks[] = add_submenu_page(
			self::LIST_SLUG,
			__( 'Marquee Settings', 'marquee-running-text' ),
			__( 'Settings', 'marquee-running-text' ),
			self::CAP,
			self::OPT_SLUG,
			array( __CLASS__, 'render_settings' )
		);

		// The 1.x "Marquee Style" screen no longer exists; send old bookmarks to the list.
		$legacy = add_submenu_page( 'options.php', '', '', self::CAP, 'mrtext_style', '__return_null' );
		if ( $legacy ) {
			add_action(
				'load-' . $legacy,
				static function () {
					wp_safe_redirect( self::url() );
					exit;
				}
			);
		}
	}

	/**
	 * URL of a plugin screen.
	 *
	 * @param string $slug Page slug.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	public static function url( $slug = self::LIST_SLUG, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $slug ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Signed admin-post.php URL for a marquee action.
	 *
	 * @param string $action Action name.
	 * @param int    $id     Marquee ID.
	 * @return string
	 */
	public static function action_url( $action, $id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'mrtext_' . $action,
					'id'     => absint( $id ),
				),
				admin_url( 'admin-post.php' )
			),
			'mrtext_' . $action . '_' . absint( $id )
		);
	}

	/**
	 * Whether the Pro add-on is active. Only used to hide upgrade prompts.
	 *
	 * @return bool
	 */
	public static function is_pro_active() {
		/**
		 * Filters whether Marquee Running Text Pro is active.
		 *
		 * @param bool $active Whether Pro is active.
		 */
		return (bool) apply_filters( 'mrtext_is_pro_active', false );
	}

	/**
	 * Where "Get Pro" buttons go: the Pro add-on pricing page.
	 *
	 * @return string
	 */
	public static function pro_url() {
		return MRTEXT_PRO_URL;
	}

	/**
	 * Loads admin assets on the plugin screens only.
	 *
	 * @param string $hook Current page hook.
	 */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}

		wp_enqueue_style( 'mrtext-admin', MRTEXT_URL . 'assets/css/mrtext-admin.css', array( 'wp-color-picker', 'mrtext' ), MRTEXT_VERSION );
		wp_enqueue_script(
			'mrtext-admin',
			MRTEXT_URL . 'assets/js/mrtext-admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'mrtext' ),
			MRTEXT_VERSION,
			true
		);
		wp_localize_script(
			'mrtext-admin',
			'mrtextAdmin',
			array(
				'i18n' => array(
					'copied'        => __( 'Copied!', 'marquee-running-text' ),
					'confirmDelete' => __( 'Delete this marquee? This cannot be undone.', 'marquee-running-text' ),
					'emptyPreview'  => __( 'Add an item to see the preview.', 'marquee-running-text' ),
					'unsaved'       => __( 'You have unsaved changes.', 'marquee-running-text' ),
					'pause'         => __( 'Pause', 'marquee-running-text' ),
					'play'          => __( 'Play', 'marquee-running-text' ),
					'close'         => __( 'Close', 'marquee-running-text' ),
				),
			)
		);
	}

	/**
	 * Adds "Settings" and "Get Pro" links on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$new = array( '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'marquee-running-text' ) . '</a>' );
		if ( ! self::is_pro_active() ) {
			$new[] = '<a href="' . esc_url( self::pro_url() ) . '" target="_blank" rel="noopener noreferrer" style="font-weight:600;color:#a31c23;">' . esc_html__( 'Get Pro', 'marquee-running-text' ) . '</a>';
		}
		return array_merge( $new, $links );
	}

	/**
	 * Registers the settings with the Settings API.
	 */
	public static function register_settings() {
		register_setting(
			'mrtext_settings',
			MRText_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'MRText_Settings', 'sanitize' ),
				'default'           => MRText_Settings::defaults(),
			)
		);
	}

	/**
	 * One-time notice after upgrading from 1.x.
	 */
	public static function migration_notice() {
		$id = absint( get_option( MRText_Migration::NOTICE_OPTION ) );
		if ( ! $id || ! current_user_can( self::CAP ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( ! in_array( $screen->id, array( 'dashboard', 'plugins', 'update-core' ), true ) && ! in_array( $screen->id, self::$hooks, true ) ) ) {
			return;
		}

		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=mrtext_dismiss_notice' ), 'mrtext_dismiss_notice' );
		?>
		<div class="notice notice-success">
			<p>
				<strong><?php esc_html_e( 'Marquee Running Text 2.0 is here!', 'marquee-running-text' ); ?></strong>
				<?php esc_html_e( 'Your texts, links and colors were moved to the new marquee builder. Nothing was lost.', 'marquee-running-text' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::url( self::EDIT_SLUG, array( 'id' => $id ) ) ); ?>"><?php esc_html_e( 'Open your marquee', 'marquee-running-text' ); ?></a>
				<a class="button" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'marquee-running-text' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Hides the 1.x migration notice.
	 */
	public static function handle_dismiss_notice() {
		self::check( 'mrtext_dismiss_notice' );
		delete_option( MRText_Migration::NOTICE_OPTION );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : self::url() );
		exit;
	}

	/**
	 * Deletes a marquee.
	 */
	public static function handle_delete() {
		$id = self::request_id();
		self::check( 'mrtext_delete_' . $id );

		if ( MRText_Post_Type::get( $id ) ) {
			wp_delete_post( $id, true );
		}
		self::redirect( 'deleted' );
	}

	/**
	 * Duplicates a marquee (as inactive).
	 */
	public static function handle_duplicate() {
		$id = self::request_id();
		self::check( 'mrtext_duplicate_' . $id );

		$post = MRText_Post_Type::get( $id );
		if ( ! $post ) {
			self::redirect( 'error' );
		}
		if ( ! MRText_Post_Type::can_create() ) {
			self::redirect( 'limit' );
		}

		/* translators: %s: marquee title. */
		$title = sprintf( __( '%s (copy)', 'marquee-running-text' ), $post->post_title );
		$new   = MRText_Post_Type::save( 0, $title, MRText_Post_Type::get_config( $id ), false );
		self::redirect( is_wp_error( $new ) ? 'error' : 'duplicated' );
	}

	/**
	 * Activates or deactivates a marquee.
	 */
	public static function handle_toggle() {
		$id = self::request_id();
		self::check( 'mrtext_toggle_' . $id );

		$post = MRText_Post_Type::get( $id );
		if ( $post ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'publish' === $post->post_status ? 'draft' : 'publish',
				)
			);
		}
		self::redirect( 'updated' );
	}

	/**
	 * Capability and nonce check for admin-post handlers.
	 *
	 * @param string $action Nonce action.
	 */
	public static function check( $action ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'marquee-running-text' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Marquee ID from the request. Nonces are verified by the caller.
	 *
	 * @return int
	 */
	private static function request_id() {
		return isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified right after in check().
	}

	/**
	 * Redirects back to the list with a message.
	 *
	 * @param string $message Message key.
	 */
	public static function redirect( $message ) {
		wp_safe_redirect( self::url( self::LIST_SLUG, array( 'mrtext_msg' => $message ) ) );
		exit;
	}

	/**
	 * Prints the message passed in the URL after an action.
	 */
	public static function print_message() {
		$key = isset( $_GET['mrtext_msg'] ) ? sanitize_key( wp_unslash( $_GET['mrtext_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.

		$messages = array(
			'saved'      => array( 'success', __( 'Marquee saved.', 'marquee-running-text' ) ),
			'updated'    => array( 'success', __( 'Marquee updated.', 'marquee-running-text' ) ),
			'deleted'    => array( 'success', __( 'Marquee deleted.', 'marquee-running-text' ) ),
			'duplicated' => array( 'success', __( 'Marquee duplicated. The copy is inactive until you activate it.', 'marquee-running-text' ) ),
			/* translators: %d: maximum number of marquees. */
			'limit'      => array( 'warning', sprintf( __( 'You have reached the limit of %d marquees. Delete one or upgrade to Pro for unlimited marquees.', 'marquee-running-text' ), MRText_Post_Type::get_limit() ) ),
			'error'      => array( 'error', __( 'Something went wrong. Please try again.', 'marquee-running-text' ) ),
		);

		if ( isset( $messages[ $key ] ) ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $messages[ $key ][0] ),
				esc_html( $messages[ $key ][1] )
			);
		}
	}

	/**
	 * Human label for a placement.
	 *
	 * @param string $placement Placement key.
	 * @return string
	 */
	public static function placement_label( $placement ) {
		$labels = array(
			'top'    => __( 'Top of page', 'marquee-running-text' ),
			'sticky' => __( 'Sticky top bar', 'marquee-running-text' ),
			'bottom' => __( 'Fixed bottom bar', 'marquee-running-text' ),
			'none'   => __( 'Shortcode / block only', 'marquee-running-text' ),
		);
		return isset( $labels[ $placement ] ) ? $labels[ $placement ] : $placement;
	}

	/**
	 * Marquee list screen.
	 */
	public static function render_list() {
		$marquees   = MRText_Post_Type::get_marquees();
		$limit      = MRText_Post_Type::get_limit();
		$can_create = MRText_Post_Type::can_create();
		$default_id = MRText_Post_Type::get_default_id();
		?>
		<div class="wrap mrtext-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Marquees', 'marquee-running-text' ); ?></h1>
			<?php if ( $can_create ) : ?>
				<a href="<?php echo esc_url( self::url( self::EDIT_SLUG ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Marquee', 'marquee-running-text' ); ?></a>
			<?php endif; ?>
			<?php if ( ! self::is_pro_active() ) : ?>
				<span class="mrtext-usage">
					<?php
					/* translators: 1: number of marquees, 2: maximum number of marquees. */
					echo esc_html( sprintf( __( '%1$d of %2$d marquees used', 'marquee-running-text' ), count( $marquees ), $limit ) );
					?>
				</span>
			<?php endif; ?>
			<hr class="wp-header-end">

			<?php self::print_message(); ?>

			<div class="mrtext-layout">
				<div class="mrtext-layout__main">
					<?php if ( ! $marquees ) : ?>
						<div class="mrtext-card mrtext-empty">
							<span class="dashicons dashicons-megaphone" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Create your first marquee', 'marquee-running-text' ); ?></h2>
							<p><?php esc_html_e( 'Show news, offers or announcements in a smooth scrolling bar.', 'marquee-running-text' ); ?></p>
							<a class="button button-primary button-hero" href="<?php echo esc_url( self::url( self::EDIT_SLUG ) ); ?>"><?php esc_html_e( 'Add New Marquee', 'marquee-running-text' ); ?></a>
						</div>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped mrtext-table">
							<thead>
								<tr>
									<th scope="col" class="column-primary"><?php esc_html_e( 'Title', 'marquee-running-text' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Placement', 'marquee-running-text' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Shortcode', 'marquee-running-text' ); ?></th>
									<th scope="col" class="mrtext-col-status"><?php esc_html_e( 'Status', 'marquee-running-text' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $marquees as $marquee ) :
									$config    = MRText_Post_Type::get_config( $marquee->ID );
									$active    = 'publish' === $marquee->post_status;
									$edit_url  = self::url( self::EDIT_SLUG, array( 'id' => $marquee->ID ) );
									$shortcode = '[mrtext id="' . $marquee->ID . '"]';
									$title     = '' !== $marquee->post_title ? $marquee->post_title : __( '(no title)', 'marquee-running-text' );
									?>
									<tr>
										<td class="column-primary" data-colname="<?php esc_attr_e( 'Title', 'marquee-running-text' ); ?>">
											<strong><a class="row-title" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $title ); ?></a></strong>
											<?php if ( $marquee->ID === $default_id ) : ?>
												<span class="mrtext-badge"><?php esc_html_e( 'Default', 'marquee-running-text' ); ?></span>
											<?php endif; ?>
											<div class="mrtext-meta">
												<?php
												/* translators: %d: number of items. */
												echo esc_html( sprintf( _n( '%d item', '%d items', count( $config['items'] ), 'marquee-running-text' ), count( $config['items'] ) ) );
												?>
											</div>
											<div class="row-actions">
												<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'marquee-running-text' ); ?></a> | </span>
												<?php if ( $can_create ) : ?>
													<span class="duplicate"><a href="<?php echo esc_url( self::action_url( 'duplicate', $marquee->ID ) ); ?>"><?php esc_html_e( 'Duplicate', 'marquee-running-text' ); ?></a> | </span>
												<?php endif; ?>
												<span class="trash"><a class="mrtext-delete" href="<?php echo esc_url( self::action_url( 'delete', $marquee->ID ) ); ?>"><?php esc_html_e( 'Delete', 'marquee-running-text' ); ?></a></span>
											</div>
										</td>
										<td data-colname="<?php esc_attr_e( 'Placement', 'marquee-running-text' ); ?>"><?php echo esc_html( self::placement_label( $config['display']['placement'] ) ); ?></td>
										<td data-colname="<?php esc_attr_e( 'Shortcode', 'marquee-running-text' ); ?>">
											<span class="mrtext-copy">
												<input type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>" aria-label="<?php esc_attr_e( 'Shortcode', 'marquee-running-text' ); ?>">
												<button type="button" class="button mrtext-copy__btn"><?php esc_html_e( 'Copy', 'marquee-running-text' ); ?></button>
											</span>
										</td>
										<td data-colname="<?php esc_attr_e( 'Status', 'marquee-running-text' ); ?>">
											<a class="mrtext-status <?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::action_url( 'toggle', $marquee->ID ) ); ?>">
												<?php echo $active ? esc_html__( 'Active', 'marquee-running-text' ) : esc_html__( 'Inactive', 'marquee-running-text' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php if ( ! $can_create && ! self::is_pro_active() ) : ?>
							<div class="mrtext-card mrtext-limit">
								<p>
									<?php
									/* translators: %d: maximum number of marquees. */
									echo esc_html( sprintf( __( 'The free version includes up to %d marquees. Upgrade to Pro for unlimited marquees and more features.', 'marquee-running-text' ), $limit ) );
									?>
								</p>
								<a class="button button-primary" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Pro', 'marquee-running-text' ); ?></a>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<div class="mrtext-layout__side">
					<?php self::render_side_cards(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Settings screen.
	 */
	public static function render_settings() {
		$settings = MRText_Settings::get();
		$name     = MRText_Settings::OPTION;
		?>
		<div class="wrap mrtext-admin">
			<h1><?php esc_html_e( 'Marquee Settings', 'marquee-running-text' ); ?></h1>
			<div class="mrtext-layout">
				<div class="mrtext-layout__main">
					<form method="post" action="options.php" class="mrtext-card">
						<?php settings_fields( 'mrtext_settings' ); ?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="mrtext-default-marquee"><?php esc_html_e( 'Default marquee', 'marquee-running-text' ); ?></label></th>
								<td>
									<select id="mrtext-default-marquee" name="<?php echo esc_attr( $name ); ?>[default_marquee]">
										<option value="0"><?php esc_html_e( 'First active marquee', 'marquee-running-text' ); ?></option>
										<?php foreach ( MRText_Post_Type::get_marquees() as $marquee ) : ?>
											<option value="<?php echo esc_attr( $marquee->ID ); ?>" <?php selected( (int) $settings['default_marquee'], $marquee->ID ); ?>><?php echo esc_html( $marquee->post_title ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Shown by the [mrtext] shortcode when no id is given.', 'marquee-running-text' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Uninstall', 'marquee-running-text' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[delete_data]" value="1" <?php checked( $settings['delete_data'] ); ?>>
										<?php esc_html_e( 'Delete all marquees and settings when the plugin is deleted', 'marquee-running-text' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Leave this off if you plan to reinstall the plugin later.', 'marquee-running-text' ); ?></p>
								</td>
							</tr>
						</table>
						<?php submit_button(); ?>
					</form>

					<div class="mrtext-card">
						<h2><?php esc_html_e( 'How to show a marquee', 'marquee-running-text' ); ?></h2>
						<ul class="mrtext-list">
							<li><?php esc_html_e( 'Header, sticky or bottom bar: choose the placement in the marquee editor (Display tab).', 'marquee-running-text' ); ?></li>
							<li><?php esc_html_e( 'Anywhere in content: use the shortcode [mrtext id="123"] or [mrtext] for the default marquee.', 'marquee-running-text' ); ?></li>
							<li><?php esc_html_e( 'Block editor: add the "Marquee Running Text" block.', 'marquee-running-text' ); ?></li>
							<li><?php esc_html_e( 'Elementor: add the "Marquee Running Text" widget.', 'marquee-running-text' ); ?></li>
							<li><?php esc_html_e( 'Theme files:', 'marquee-running-text' ); ?> <code>&lt;?php echo do_shortcode( '[mrtext]' ); ?&gt;</code></li>
						</ul>
					</div>
				</div>
				<div class="mrtext-layout__side">
					<?php self::render_side_cards(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Sidebar cards: Pro upgrade and support.
	 */
	public static function render_side_cards() {
		if ( ! self::is_pro_active() ) :
			?>
			<div class="mrtext-card mrtext-pro">
				<h2><?php esc_html_e( 'Marquee Running Text Pro', 'marquee-running-text' ); ?></h2>
				<ul>
					<li><?php esc_html_e( 'Unlimited marquees', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Ready-made templates', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Logo and image marquees', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Latest posts and WooCommerce product tickers', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Display conditions and scheduling', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Vertical, typewriter and fade effects', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Google Fonts, gradients and custom CSS', 'marquee-running-text' ); ?></li>
					<li><?php esc_html_e( 'Priority support', 'marquee-running-text' ); ?></li>
				</ul>
				<a class="button button-primary" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Pro', 'marquee-running-text' ); ?></a>
			</div>
		<?php endif; ?>
		<div class="mrtext-card">
			<h2><?php esc_html_e( 'Need help?', 'marquee-running-text' ); ?></h2>
			<p><a href="https://wordpress.org/support/plugin/marquee-running-text/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support forum', 'marquee-running-text' ); ?></a></p>
			<p><a href="https://wordpress.org/support/plugin/marquee-running-text/reviews/#new-post" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Enjoying the plugin? Leave a review', 'marquee-running-text' ); ?></a></p>
			<p><a href="https://buymeacoffee.com/hasanjahid" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Buy me a coffee', 'marquee-running-text' ); ?></a></p>
			<p class="mrtext-credit"><?php esc_html_e( 'Made by Jahid Hasan · Bongdevs', 'marquee-running-text' ); ?></p>
		</div>
		<?php
	}
}
