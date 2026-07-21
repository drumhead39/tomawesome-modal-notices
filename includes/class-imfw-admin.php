<?php
/**
 * Admin editor for information modals.
 *
 * @package TomAwesomeModalNotices
 */

defined( 'ABSPATH' ) || exit;

class IMFW_Admin {
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . IMFW_Post_Type::TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_' . IMFW_Post_Type::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . IMFW_Post_Type::TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || IMFW_Post_Type::TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'imfw-admin', IMFW_URL . 'assets/css/admin.css', array(), IMFW_VERSION );
		wp_enqueue_script( 'imfw-admin', IMFW_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), IMFW_VERSION, true );
	}

	public function add_meta_boxes() {
		add_meta_box( 'imfw_behavior', __( 'Display & Behavior', 'tomawesome-modal-notices' ), array( $this, 'behavior_box' ), IMFW_Post_Type::TYPE, 'normal', 'high' );
		add_meta_box( 'imfw_targeting', __( 'Targeting', 'tomawesome-modal-notices' ), array( $this, 'targeting_box' ), IMFW_Post_Type::TYPE, 'normal' );
		add_meta_box( 'imfw_design', __( 'Design', 'tomawesome-modal-notices' ), array( $this, 'design_box' ), IMFW_Post_Type::TYPE, 'normal' );
	}

	private function value( $post, $key ) {
		return IMFW_Settings::get( $post->ID, $key );
	}

	private function row( $label, $html, $help = '' ) {
		echo '<div class="imfw-field"><div class="imfw-field__label">' . esc_html( $label ) . '</div><div class="imfw-field__control">';
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constructed from escaped values in the rendering methods.
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</div></div>';
	}

	private function select( $name, $value, $options ) {
		$html = '<select name="imfw[' . esc_attr( $name ) . ']">';
		foreach ( $options as $key => $label ) {
			$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $html . '</select>';
	}

	public function behavior_box( $post ) {
		wp_nonce_field( 'imfw_save_modal', 'imfw_nonce' );
		echo '<div class="imfw-grid">';

		$this->row(
			__( 'Status', 'tomawesome-modal-notices' ),
			'<label><input type="checkbox" name="imfw[enabled]" value="1" ' . checked( $this->value( $post, 'enabled' ), '1', false ) . '> ' . esc_html__( 'Enabled', 'tomawesome-modal-notices' ) . '</label>'
		);
		$this->row(
			__( 'Open trigger', 'tomawesome-modal-notices' ),
			$this->select(
				'trigger',
				$this->value( $post, 'trigger' ),
				array(
					'load'   => __( 'On page load', 'tomawesome-modal-notices' ),
					'delay'  => __( 'After a delay', 'tomawesome-modal-notices' ),
					'scroll' => __( 'After scrolling', 'tomawesome-modal-notices' ),
					'exit'   => __( 'Exit intent', 'tomawesome-modal-notices' ),
					'click'  => __( 'Element click', 'tomawesome-modal-notices' ),
				)
			)
		);
		$this->row(
			__( 'Delay (milliseconds)', 'tomawesome-modal-notices' ),
			'<input type="number" min="0" max="3600000" step="100" name="imfw[delay]" value="' . esc_attr( $this->value( $post, 'delay' ) ) . '">',
			__( 'Used only with the “After a delay” trigger. One second equals 1,000 milliseconds.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Scroll percentage', 'tomawesome-modal-notices' ),
			'<input type="number" min="1" max="100" name="imfw[scroll]" value="' . esc_attr( $this->value( $post, 'scroll' ) ) . '">',
			__( 'The percentage of the page a visitor must scroll before the modal opens.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Click CSS selector', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[selector]" value="' . esc_attr( $this->value( $post, 'selector' ) ) . '" placeholder="#open-modal, .my-button">',
			__( 'Used only with the “Element click” trigger. Enter a valid CSS selector.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Position', 'tomawesome-modal-notices' ),
			$this->select(
				'placement',
				$this->value( $post, 'placement' ),
				array(
					'center'       => __( 'Center', 'tomawesome-modal-notices' ),
					'top'          => __( 'Top banner', 'tomawesome-modal-notices' ),
					'bottom'       => __( 'Bottom banner', 'tomawesome-modal-notices' ),
					'left'         => __( 'Floating left', 'tomawesome-modal-notices' ),
					'right'        => __( 'Floating right', 'tomawesome-modal-notices' ),
					'top-left'     => __( 'Top left', 'tomawesome-modal-notices' ),
					'top-right'    => __( 'Top right', 'tomawesome-modal-notices' ),
					'bottom-left'  => __( 'Bottom left', 'tomawesome-modal-notices' ),
					'bottom-right' => __( 'Bottom right', 'tomawesome-modal-notices' ),
				)
			)
		);
		$this->row(
			__( 'Opening animation', 'tomawesome-modal-notices' ),
			$this->select(
				'animation',
				$this->value( $post, 'animation' ),
				array(
					'none'        => __( 'None', 'tomawesome-modal-notices' ),
					'fade'        => __( 'Fade', 'tomawesome-modal-notices' ),
					'scale'       => __( 'Scale', 'tomawesome-modal-notices' ),
					'slide-up'    => __( 'Slide up', 'tomawesome-modal-notices' ),
					'slide-down'  => __( 'Slide down', 'tomawesome-modal-notices' ),
					'slide-left'  => __( 'Slide left', 'tomawesome-modal-notices' ),
					'slide-right' => __( 'Slide right', 'tomawesome-modal-notices' ),
				)
			)
		);
		$this->row(
			__( 'Close button', 'tomawesome-modal-notices' ),
			'<label><input type="checkbox" name="imfw[close_x]" value="1" ' . checked( $this->value( $post, 'close_x' ), '1', false ) . '> ' . esc_html__( 'Show close “X”', 'tomawesome-modal-notices' ) . '</label>'
		);
		$this->row( __( 'Confirm button text', 'tomawesome-modal-notices' ), '<input type="text" name="imfw[confirm_text]" value="' . esc_attr( $this->value( $post, 'confirm_text' ) ) . '">' );
		$this->row(
			__( 'Confirm action', 'tomawesome-modal-notices' ),
			$this->select(
				'confirm_action',
				$this->value( $post, 'confirm_action' ),
				array(
					'close'   => __( 'Close modal', 'tomawesome-modal-notices' ),
					'url'     => __( 'Open URL in same tab', 'tomawesome-modal-notices' ),
					'url_new' => __( 'Open URL in new tab', 'tomawesome-modal-notices' ),
				)
			)
		);
		$this->row( __( 'Confirm URL', 'tomawesome-modal-notices' ), '<input type="url" name="imfw[confirm_url]" value="' . esc_attr( $this->value( $post, 'confirm_url' ) ) . '" placeholder="https://">' );
		$this->row(
			__( 'Frequency', 'tomawesome-modal-notices' ),
			$this->select(
				'frequency',
				$this->value( $post, 'frequency' ),
				array(
					'always'  => __( 'Every page load', 'tomawesome-modal-notices' ),
					'session' => __( 'Once per browser session', 'tomawesome-modal-notices' ),
					'once'    => __( 'Once per visitor', 'tomawesome-modal-notices' ),
					'days'    => __( 'Repeat after a number of days', 'tomawesome-modal-notices' ),
				)
			)
		);
		$this->row( __( 'Repeat after days', 'tomawesome-modal-notices' ), '<input type="number" min="1" max="3650" name="imfw[repeat_days]" value="' . esc_attr( $this->value( $post, 'repeat_days' ) ) . '">' );
		$this->row(
			__( 'Frequency scope', 'tomawesome-modal-notices' ),
			$this->select(
				'frequency_scope',
				$this->value( $post, 'frequency_scope' ),
				array(
					'site' => __( 'Across the website', 'tomawesome-modal-notices' ),
					'page' => __( 'Separately for each page', 'tomawesome-modal-notices' ),
				)
			)
		);
		$this->row(
			__( 'Start date/time', 'tomawesome-modal-notices' ),
			'<input type="datetime-local" name="imfw[start]" value="' . esc_attr( $this->value( $post, 'start' ) ) . '">',
			__( 'Uses the timezone selected in WordPress Settings.', 'tomawesome-modal-notices' )
		);
		$this->row( __( 'End date/time', 'tomawesome-modal-notices' ), '<input type="datetime-local" name="imfw[end]" value="' . esc_attr( $this->value( $post, 'end' ) ) . '">' );

		echo '</div>';
	}

	public function targeting_box( $post ) {
		echo '<div class="imfw-grid">';
		$this->row(
			__( 'Target mode', 'tomawesome-modal-notices' ),
			$this->select(
				'target_mode',
				$this->value( $post, 'target_mode' ),
				array(
					'sitewide'   => __( 'Entire website', 'tomawesome-modal-notices' ),
					'homepage'   => __( 'Homepage only', 'tomawesome-modal-notices' ),
					'singular'   => __( 'Selected content', 'tomawesome-modal-notices' ),
					'post_types' => __( 'Selected post types', 'tomawesome-modal-notices' ),
					'taxonomy'   => __( 'Taxonomies and matching content', 'tomawesome-modal-notices' ),
					'archives'   => __( 'All archive pages', 'tomawesome-modal-notices' ),
				)
			)
		);

		$checks   = '';
		$selected = (array) $this->value( $post, 'post_types' );
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $type ) {
			$checks .= '<label><input type="checkbox" name="imfw[post_types][]" value="' . esc_attr( $type->name ) . '" ' . checked( in_array( $type->name, $selected, true ), true, false ) . '> ' . esc_html( $type->labels->singular_name ) . '</label> ';
		}
		$this->row( __( 'Post types', 'tomawesome-modal-notices' ), $checks );
		$this->row(
			__( 'Include content IDs', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[include_ids]" value="' . esc_attr( $this->value( $post, 'include_ids' ) ) . '" placeholder="12, 34, 56">',
			__( 'Comma-separated page, post, or custom post IDs.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Exclude content IDs', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[exclude_ids]" value="' . esc_attr( $this->value( $post, 'exclude_ids' ) ) . '" placeholder="78, 90">',
			__( 'Exclusions override all inclusion rules on singular content.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Taxonomy rules', 'tomawesome-modal-notices' ),
			'<textarea name="imfw[tax_rules]" rows="4" placeholder="category:news,offers&#10;post_tag:featured">' . esc_textarea( $this->value( $post, 'tax_rules' ) ) . '</textarea>',
			__( 'One taxonomy per line using taxonomy:term-slug,term-slug. Matches archives and singular content assigned to a term.', 'tomawesome-modal-notices' )
		);
		echo '</div>';
	}

	public function design_box( $post ) {
		echo '<div class="imfw-grid">';
		$fields = array(
			'width'       => array( __( 'Width', 'tomawesome-modal-notices' ), '90%' ),
			'max_width'   => array( __( 'Maximum width', 'tomawesome-modal-notices' ), '600px' ),
			'padding'     => array( __( 'Padding', 'tomawesome-modal-notices' ), '28px' ),
			'radius'      => array( __( 'Corner radius', 'tomawesome-modal-notices' ), '8px' ),
			'font_size'   => array( __( 'Font size', 'tomawesome-modal-notices' ), '16px' ),
			'font_family' => array( __( 'Font family', 'tomawesome-modal-notices' ), 'inherit' ),
		);
		foreach ( $fields as $key => $data ) {
			$this->row( $data[0], '<input type="text" name="imfw[' . esc_attr( $key ) . ']" value="' . esc_attr( $this->value( $post, $key ) ) . '" placeholder="' . esc_attr( $data[1] ) . '">' );
		}

		$colors = array(
			'bg'                => __( 'Background color', 'tomawesome-modal-notices' ),
			'text_color'        => __( 'Text color', 'tomawesome-modal-notices' ),
			'overlay'           => __( 'Overlay color', 'tomawesome-modal-notices' ),
			'button_bg'         => __( 'Button background', 'tomawesome-modal-notices' ),
			'button_text'       => __( 'Button text', 'tomawesome-modal-notices' ),
			'button_border'     => __( 'Button border', 'tomawesome-modal-notices' ),
			'button_hover_bg'   => __( 'Button hover background', 'tomawesome-modal-notices' ),
			'button_hover_text' => __( 'Button hover text', 'tomawesome-modal-notices' ),
		);
		foreach ( $colors as $key => $label ) {
			$this->row( $label, '<input class="imfw-color" type="text" name="imfw[' . esc_attr( $key ) . ']" value="' . esc_attr( $this->value( $post, $key ) ) . '">' );
		}
		$this->row(
			__( 'Custom CSS class', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[custom_class]" value="' . esc_attr( $this->value( $post, 'custom_class' ) ) . '" placeholder="my-modal">',
			__( 'Added to this modal only. Use your theme stylesheet for advanced styling.', 'tomawesome-modal-notices' )
		);
		echo '</div>';
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['imfw_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['imfw_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'imfw_save_modal' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Each submitted value is sanitized by IMFW_Settings::sanitize() immediately below according to its expected type.
		$submitted = isset( $_POST['imfw'] ) && is_array( $_POST['imfw'] ) ? wp_unslash( $_POST['imfw'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$defaults  = IMFW_Settings::defaults();

		foreach ( array_keys( $defaults ) as $key ) {
			$value = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
			update_post_meta( $post_id, '_imfw_' . $key, IMFW_Settings::sanitize( $key, $value ) );
		}
	}

	public function columns( $columns ) {
		$columns['imfw_status'] = __( 'Status', 'tomawesome-modal-notices' );
		$columns['imfw_target'] = __( 'Target', 'tomawesome-modal-notices' );
		return $columns;
	}

	public function column_content( $column, $post_id ) {
		if ( 'imfw_status' === $column ) {
			echo '1' === IMFW_Settings::get( $post_id, 'enabled' ) ? esc_html__( 'Enabled', 'tomawesome-modal-notices' ) : esc_html__( 'Disabled', 'tomawesome-modal-notices' );
		}

		if ( 'imfw_target' === $column ) {
			$labels = array(
				'sitewide'   => __( 'Entire website', 'tomawesome-modal-notices' ),
				'homepage'   => __( 'Homepage', 'tomawesome-modal-notices' ),
				'singular'   => __( 'Selected content', 'tomawesome-modal-notices' ),
				'post_types' => __( 'Post types', 'tomawesome-modal-notices' ),
				'taxonomy'   => __( 'Taxonomy', 'tomawesome-modal-notices' ),
				'archives'   => __( 'Archives', 'tomawesome-modal-notices' ),
			);
			$mode = IMFW_Settings::get( $post_id, 'target_mode' );
			echo esc_html( isset( $labels[ $mode ] ) ? $labels[ $mode ] : $mode );
		}
	}
}
