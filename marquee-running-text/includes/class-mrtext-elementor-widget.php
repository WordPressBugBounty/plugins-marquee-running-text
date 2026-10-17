<?php
/**
 * Elementor widget. Loaded only from the "elementor/widgets/register" hook.
 *
 * @package MarqueeRunningText
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Marquee Running Text" Elementor widget.
 */
class MRText_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'mrtext-marquee';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Marquee Running Text', 'marquee-running-text' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-animation-text';
	}

	/**
	 * Widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'marquee', 'ticker', 'news', 'scrolling', 'announcement' );
	}

	/**
	 * Styles the widget needs.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'mrtext' );
	}

	/**
	 * Scripts the widget needs.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'mrtext' );
	}

	/**
	 * Widget controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_marquee',
			array( 'label' => __( 'Marquee', 'marquee-running-text' ) )
		);

		$this->add_control(
			'marquee_id',
			array(
				'label'   => __( 'Choose a marquee', 'marquee-running-text' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => MRText_Elementor::marquee_options(),
				'default' => '0',
			)
		);

		$this->add_control(
			'marquee_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Edit text, colors and speed under Marquee in the WordPress admin menu.', 'marquee-running-text' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Front-end output.
	 */
	protected function render() {
		$id = absint( $this->get_settings_for_display( 'marquee_id' ) );
		echo MRText_Frontend::render_inline( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in MRText_Renderer.
	}
}
