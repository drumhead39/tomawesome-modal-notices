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
		wp_enqueue_style( 'dashicons' );
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

	private function row( $label, $html, $help ) {
		$tooltip_id = wp_unique_id( 'imfw-tooltip-' );

		/* translators: %s: setting label. */
		$tooltip_label = sprintf( __( 'Help for %s', 'tomawesome-modal-notices' ), $label );

		echo '<div class="imfw-field"><div class="imfw-field__label"><span>' . esc_html( $label ) . '</span>';
		echo '<span class="imfw-tooltip">';
		echo '<button type="button" class="imfw-tooltip__trigger" aria-label="' . esc_attr( $tooltip_label ) . '" aria-describedby="' . esc_attr( $tooltip_id ) . '">';
		echo '<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>';
		echo '</button>';
		echo '<span id="' . esc_attr( $tooltip_id ) . '" class="imfw-tooltip__content" role="tooltip">' . esc_html( $help ) . '</span>';
		echo '</span></div><div class="imfw-field__control">';
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constructed from escaped values in the rendering methods.
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
			'<label><input type="checkbox" name="imfw[enabled]" value="1" ' . checked( $this->value( $post, 'enabled' ), '1', false ) . '> ' . esc_html__( 'Enabled', 'tomawesome-modal-notices' ) . '</label>',
			__( 'Controls whether this notice can appear on the website. A disabled notice remains saved but is never shown to visitors.', 'tomawesome-modal-notices' )
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
			),
			__( 'Chooses what opens the notice: loading the page, waiting, scrolling, desktop exit intent, or clicking a matching element.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Delay (milliseconds)', 'tomawesome-modal-notices' ),
			'<input type="number" min="0" max="3600000" step="100" name="imfw[delay]" value="' . esc_attr( $this->value( $post, 'delay' ) ) . '">',
			__( 'Used only with “After a delay.” One second equals 1,000 milliseconds, so 3000 waits three seconds.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Scroll percentage', 'tomawesome-modal-notices' ),
			'<input type="number" min="1" max="100" name="imfw[scroll]" value="' . esc_attr( $this->value( $post, 'scroll' ) ) . '">',
			__( 'Used only with “After scrolling.” Enter 1–100; for example, 50 opens the notice halfway down the page.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Click CSS selector', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[selector]" value="' . esc_attr( $this->value( $post, 'selector' ) ) . '" placeholder="#open-modal, .my-button">',
			__( 'Used only with “Element click.” Enter a valid CSS selector such as #open-modal, .my-button, or multiple selectors separated by commas.', 'tomawesome-modal-notices' )
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
			),
			__( 'Sets where the notice appears. Top and bottom banners span the window; floating and corner positions stay near the selected edge.', 'tomawesome-modal-notices' )
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
			),
			__( 'Adds entrance motion when the notice opens. Visitors who request reduced motion will not see the animation.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Close button', 'tomawesome-modal-notices' ),
			'<label><input type="checkbox" name="imfw[close_x]" value="1" ' . checked( $this->value( $post, 'close_x' ), '1', false ) . '> ' . esc_html__( 'Show close “X”', 'tomawesome-modal-notices' ) . '</label>',
			__( 'Shows an “X” in the upper-right corner. Visitors can also dismiss the notice with Escape or by clicking the overlay.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Confirm button text', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[confirm_text]" value="' . esc_attr( $this->value( $post, 'confirm_text' ) ) . '">',
			__( 'Sets the label of the main button, such as “Got it” or “Learn more.” Leave blank to hide the button.', 'tomawesome-modal-notices' )
		);
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
			),
			__( 'Chooses what happens when the visitor selects the confirm button: close the notice or open the Confirm URL.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Confirm URL', 'tomawesome-modal-notices' ),
			'<input type="url" name="imfw[confirm_url]" value="' . esc_attr( $this->value( $post, 'confirm_url' ) ) . '" placeholder="https://">',
			__( 'Used only with a URL confirm action. Enter the complete destination beginning with https:// or http://.', 'tomawesome-modal-notices' )
		);
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
			),
			__( 'Limits how often this notice appears in the same browser. Session and visitor rules use browser storage, not a WordPress user account.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Repeat after days', 'tomawesome-modal-notices' ),
			'<input type="number" min="1" max="3650" name="imfw[repeat_days]" value="' . esc_attr( $this->value( $post, 'repeat_days' ) ) . '">',
			__( 'Used only with “Repeat after a number of days.” The notice becomes eligible again after this many full days.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Frequency scope', 'tomawesome-modal-notices' ),
			$this->select(
				'frequency_scope',
				$this->value( $post, 'frequency_scope' ),
				array(
					'site' => __( 'Across the website', 'tomawesome-modal-notices' ),
					'page' => __( 'Separately for each page', 'tomawesome-modal-notices' ),
				)
			),
			__( 'Controls whether the frequency limit applies once across the whole website or starts a separate count for each page.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Start date/time', 'tomawesome-modal-notices' ),
			'<input type="datetime-local" name="imfw[start]" value="' . esc_attr( $this->value( $post, 'start' ) ) . '">',
			__( 'The notice will not appear before this date and time. Leave blank to start immediately. Uses the timezone in WordPress Settings.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'End date/time', 'tomawesome-modal-notices' ),
			'<input type="datetime-local" name="imfw[end]" value="' . esc_attr( $this->value( $post, 'end' ) ) . '">',
			__( 'The notice stops appearing after this date and time. Leave blank for no expiration. Uses the timezone in WordPress Settings.', 'tomawesome-modal-notices' )
		);

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
			),
			__( 'Chooses which part of the website can show this notice. The related fields below are used only when their matching mode is selected.', 'tomawesome-modal-notices' )
		);

		$checks   = '';
		$selected = (array) $this->value( $post, 'post_types' );
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $type ) {
			$checks .= '<label><input type="checkbox" name="imfw[post_types][]" value="' . esc_attr( $type->name ) . '" ' . checked( in_array( $type->name, $selected, true ), true, false ) . '> ' . esc_html( $type->labels->singular_name ) . '</label> ';
		}
		$this->row(
			__( 'Post types', 'tomawesome-modal-notices' ),
			$checks,
			__( 'Used only with “Selected post types.” The notice appears on individual items belonging to any checked public post type.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Include content IDs', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[include_ids]" value="' . esc_attr( $this->value( $post, 'include_ids' ) ) . '" placeholder="12, 34, 56">',
			__( 'Used only with “Selected content.” Enter comma-separated page, post, or custom post IDs, such as 12, 34, 56.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Exclude content IDs', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[exclude_ids]" value="' . esc_attr( $this->value( $post, 'exclude_ids' ) ) . '" placeholder="78, 90">',
			__( 'Enter comma-separated content IDs that must never show this notice. Exclusions override all inclusion rules on individual content.', 'tomawesome-modal-notices' )
		);
		$this->row(
			__( 'Taxonomy rules', 'tomawesome-modal-notices' ),
			'<textarea name="imfw[tax_rules]" rows="4" placeholder="category:news,offers&#10;post_tag:featured">' . esc_textarea( $this->value( $post, 'tax_rules' ) ) . '</textarea>',
			__( 'Used only with “Taxonomies and matching content.” Add one taxonomy per line, for example category:news,offers or post_tag:featured.', 'tomawesome-modal-notices' )
		);
		echo '</div>';
	}

	public function design_box( $post ) {
		echo '<div class="imfw-grid">';
		$fields = array(
			'width'       => array(
				__( 'Width', 'tomawesome-modal-notices' ),
				'90%',
				__( 'Sets the notice width using a CSS value such as 90%, 420px, or 30rem. Top and bottom banners always span the window.', 'tomawesome-modal-notices' ),
			),
			'max_width'   => array(
				__( 'Maximum width', 'tomawesome-modal-notices' ),
				'600px',
				__( 'Caps the width on larger screens using a CSS value such as 600px. Top and bottom banners do not use this limit.', 'tomawesome-modal-notices' ),
			),
			'padding'     => array(
				__( 'Padding', 'tomawesome-modal-notices' ),
				'28px',
				__( 'Controls the space between the notice content and its edges. Enter a CSS value such as 28px or 1.5rem.', 'tomawesome-modal-notices' ),
			),
			'radius'      => array(
				__( 'Corner radius', 'tomawesome-modal-notices' ),
				'8px',
				__( 'Controls corner rounding. Use 0 for square corners or a CSS value such as 8px. Banners keep square corners.', 'tomawesome-modal-notices' ),
			),
			'font_size'   => array(
				__( 'Font size', 'tomawesome-modal-notices' ),
				'16px',
				__( 'Sets the base text size inside the notice using a CSS value such as 16px, 1rem, or 110%.', 'tomawesome-modal-notices' ),
			),
			'font_family' => array(
				__( 'Font family', 'tomawesome-modal-notices' ),
				'inherit',
				__( 'Sets the CSS font family for the notice. Use inherit to match the website, or enter a font stack such as Arial, sans-serif.', 'tomawesome-modal-notices' ),
			),
		);
		foreach ( $fields as $key => $data ) {
			$this->row( $data[0], '<input type="text" name="imfw[' . esc_attr( $key ) . ']" value="' . esc_attr( $this->value( $post, $key ) ) . '" placeholder="' . esc_attr( $data[1] ) . '">', $data[2] );
		}

		$colors = array(
			'bg'                => array(
				__( 'Background color', 'tomawesome-modal-notices' ),
				__( 'Sets the background color of the notice panel.', 'tomawesome-modal-notices' ),
			),
			'text_color'        => array(
				__( 'Text color', 'tomawesome-modal-notices' ),
				__( 'Sets the default color of the notice text and close icon.', 'tomawesome-modal-notices' ),
			),
			'overlay'           => array(
				__( 'Overlay color', 'tomawesome-modal-notices' ),
				__( 'Sets the color behind the notice. Use rgba(), such as rgba(0,0,0,.55), to control transparency.', 'tomawesome-modal-notices' ),
			),
			'button_bg'         => array(
				__( 'Button background', 'tomawesome-modal-notices' ),
				__( 'Sets the normal background color of the confirm button.', 'tomawesome-modal-notices' ),
			),
			'button_text'       => array(
				__( 'Button text', 'tomawesome-modal-notices' ),
				__( 'Sets the normal text color of the confirm button.', 'tomawesome-modal-notices' ),
			),
			'button_border'     => array(
				__( 'Button border', 'tomawesome-modal-notices' ),
				__( 'Sets the border color around the confirm button.', 'tomawesome-modal-notices' ),
			),
			'button_hover_bg'   => array(
				__( 'Button hover background', 'tomawesome-modal-notices' ),
				__( 'Sets the confirm button background when a visitor hovers over it or focuses it with a keyboard.', 'tomawesome-modal-notices' ),
			),
			'button_hover_text' => array(
				__( 'Button hover text', 'tomawesome-modal-notices' ),
				__( 'Sets the confirm button text color when a visitor hovers over it or focuses it with a keyboard.', 'tomawesome-modal-notices' ),
			),
		);
		foreach ( $colors as $key => $data ) {
			$this->row( $data[0], '<input class="imfw-color" type="text" name="imfw[' . esc_attr( $key ) . ']" value="' . esc_attr( $this->value( $post, $key ) ) . '">', $data[1] );
		}
		$this->row(
			__( 'Custom CSS class', 'tomawesome-modal-notices' ),
			'<input type="text" name="imfw[custom_class]" value="' . esc_attr( $this->value( $post, 'custom_class' ) ) . '" placeholder="my-modal">',
			__( 'Adds one CSS class to this notice only. Enter the class name without a leading period, then style it in your theme stylesheet.', 'tomawesome-modal-notices' )
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
