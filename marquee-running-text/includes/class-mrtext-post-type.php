<?php
/**
 * Marquee storage: a private custom post type.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the marquee post type and reads/writes marquees.
 */
class MRText_Post_Type {

	const POST_TYPE  = 'mrtext_marquee';
	const META_KEY   = '_mrtext_config';
	const FREE_LIMIT = 5;

	/**
	 * Registers the post type.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Marquees', 'marquee-running-text' ),
					'singular_name' => __( 'Marquee', 'marquee-running-text' ),
				),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'can_export'      => true,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Maximum number of marquees. The Pro add-on raises this through the filter.
	 *
	 * @return int
	 */
	public static function get_limit() {
		/**
		 * Filters the maximum number of marquees.
		 *
		 * @param int $limit Maximum number of marquees.
		 */
		return max( 1, (int) apply_filters( 'mrtext_max_marquees', self::FREE_LIMIT ) );
	}

	/**
	 * Number of stored marquees (active and inactive).
	 *
	 * @return int
	 */
	public static function count_all() {
		$counts = wp_count_posts( self::POST_TYPE );
		return (int) $counts->publish + (int) $counts->draft;
	}

	/**
	 * Whether another marquee can be created.
	 *
	 * @return bool
	 */
	public static function can_create() {
		return self::count_all() < self::get_limit();
	}

	/**
	 * Returns marquee posts.
	 *
	 * @param string|array $status Post status(es). Active marquees are "publish", inactive are "draft".
	 * @return WP_Post[]
	 */
	public static function get_marquees( $status = array( 'publish', 'draft' ) ) {
		return get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => $status,
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
	}

	/**
	 * Returns a marquee post, or null.
	 *
	 * @param int $id Post ID.
	 * @return WP_Post|null
	 */
	public static function get( $id ) {
		$post = get_post( absint( $id ) );
		return ( $post && self::POST_TYPE === $post->post_type ) ? $post : null;
	}

	/**
	 * Returns the sanitized config of a marquee.
	 *
	 * @param int $id Post ID.
	 * @return array
	 */
	public static function get_config( $id ) {
		return MRText_Config::sanitize( get_post_meta( $id, self::META_KEY, true ) );
	}

	/**
	 * Creates or updates a marquee.
	 *
	 * @param int    $id     Post ID, 0 to create.
	 * @param string $title  Title.
	 * @param array  $config Raw config; sanitized here.
	 * @param bool   $active Whether the marquee is active.
	 * @return int|WP_Error Post ID on success.
	 */
	public static function save( $id, $title, array $config, $active ) {
		$postarr = array(
			'post_type'   => self::POST_TYPE,
			'post_title'  => sanitize_text_field( $title ),
			'post_status' => $active ? 'publish' : 'draft',
		);

		if ( $id ) {
			$postarr['ID'] = absint( $id );
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$result = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_post_meta( $result, self::META_KEY, wp_slash( MRText_Config::sanitize( $config ) ) );

		/**
		 * Fires after a marquee is saved.
		 *
		 * @param int   $result Post ID.
		 * @param array $config Raw config.
		 */
		do_action( 'mrtext_marquee_saved', $result, $config );

		return $result;
	}

	/**
	 * The marquee used by [mrtext] without an id: the chosen default, else the first active one.
	 *
	 * @return int
	 */
	public static function get_default_id() {
		$id = absint( MRText_Settings::get( 'default_marquee' ) );
		if ( $id && 'publish' === get_post_status( $id ) && self::get( $id ) ) {
			return $id;
		}

		$active = self::get_marquees( 'publish' );
		return $active ? $active[0]->ID : 0;
	}
}
