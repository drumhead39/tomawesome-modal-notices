<?php
/**
 * Frontend targeting, assets, and modal markup.
 *
 * @package TomAwesomeModalNotices
 */

defined( 'ABSPATH' ) || exit;

class IMFW_Frontend {
	/** @var WP_Post[] */
	private $modals = array();

	public function __construct() {
		add_action( 'wp', array( $this, 'prepare' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ), 20 );
	}

	public function prepare() {
		if ( is_admin() || is_feed() || wp_doing_ajax() ) {
			return;
		}

		$modals = get_posts(
			array(
				'post_type'              => IMFW_Post_Type::TYPE,
				'post_status'            => 'publish',
				'numberposts'            => -1,
				'orderby'                => 'menu_order date',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $modals as $modal ) {
			$should_display = $this->is_active( $modal->ID ) && $this->matches_request( $modal->ID );

			/**
			 * Filter whether a modal should be rendered for the current request.
			 *
			 * @param bool $should_display Whether the modal should render.
			 * @param int  $modal_id       Modal post ID.
			 */
			if ( apply_filters( 'imfw_should_display_modal', $should_display, $modal->ID ) ) {
				$this->modals[] = $modal;
			}
		}
	}

	private function is_active( $modal_id ) {
		if ( '1' !== IMFW_Settings::get( $modal_id, 'enabled' ) ) {
			return false;
		}

		$now   = current_datetime()->getTimestamp();
		$start = $this->date_to_timestamp( IMFW_Settings::get( $modal_id, 'start' ) );
		$end   = $this->date_to_timestamp( IMFW_Settings::get( $modal_id, 'end' ) );

		return ( ! $start || $now >= $start ) && ( ! $end || $now <= $end );
	}

	private function date_to_timestamp( $value ) {
		if ( ! $value ) {
			return 0;
		}

		try {
			$date = new DateTimeImmutable( $value, wp_timezone() );
			return $date->getTimestamp();
		} catch ( Exception $exception ) {
			return 0;
		}
	}

	private function content_ids( $value ) {
		return array_values( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
	}

	private function taxonomy_rules( $value ) {
		$rules = array();
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( ':', $line, 2 ) );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$taxonomy = sanitize_key( $parts[0] );
			$terms    = array_filter( array_map( 'sanitize_title', explode( ',', $parts[1] ) ) );
			if ( taxonomy_exists( $taxonomy ) && $terms ) {
				$rules[ $taxonomy ] = $terms;
			}
		}

		return $rules;
	}

	private function matches_request( $modal_id ) {
		$current_id = get_queried_object_id();

		if ( is_singular() && in_array( $current_id, $this->content_ids( IMFW_Settings::get( $modal_id, 'exclude_ids' ) ), true ) ) {
			return false;
		}

		$mode = IMFW_Settings::get( $modal_id, 'target_mode' );
		switch ( $mode ) {
			case 'sitewide':
				return true;
			case 'homepage':
				return is_front_page();
			case 'archives':
				return is_archive();
			case 'singular':
				return is_singular() && in_array( $current_id, $this->content_ids( IMFW_Settings::get( $modal_id, 'include_ids' ) ), true );
			case 'post_types':
				return is_singular( (array) IMFW_Settings::get( $modal_id, 'post_types' ) );
			case 'taxonomy':
				return $this->matches_taxonomy_rules( $modal_id, $current_id );
			default:
				return false;
		}
	}

	private function matches_taxonomy_rules( $modal_id, $current_id ) {
		$rules = $this->taxonomy_rules( IMFW_Settings::get( $modal_id, 'tax_rules' ) );

		foreach ( $rules as $taxonomy => $terms ) {
			$is_matching_archive = is_tax( $taxonomy, $terms )
				|| ( 'category' === $taxonomy && is_category( $terms ) )
				|| ( 'post_tag' === $taxonomy && is_tag( $terms ) );

			if ( $is_matching_archive || ( is_singular() && has_term( $terms, $taxonomy, $current_id ) ) ) {
				return true;
			}
		}

		return false;
	}

	public function enqueue_assets() {
		if ( empty( $this->modals ) ) {
			return;
		}

		wp_enqueue_style( 'imfw', IMFW_URL . 'assets/css/frontend.css', array(), IMFW_VERSION );
		wp_enqueue_script( 'imfw', IMFW_URL . 'assets/js/frontend.js', array(), IMFW_VERSION, true );
		wp_add_inline_script(
			'imfw',
			'window.IMFW_CONFIG = ' . wp_json_encode(
				array(
					'pageId'        => get_queried_object_id(),
					'storagePrefix' => 'imfw_v1_',
				)
			) . ';',
			'before'
		);
	}

	private function inline_style( $modal_id ) {
		$properties = array(
			'width'             => 'width',
			'max_width'         => 'max-width',
			'padding'           => 'padding',
			'radius'            => 'radius',
			'bg'                => 'background',
			'text_color'        => 'text',
			'font_size'         => 'font-size',
			'font_family'       => 'font-family',
			'overlay'           => 'overlay',
			'button_bg'         => 'button-bg',
			'button_text'       => 'button-text',
			'button_border'     => 'button-border',
			'button_hover_bg'   => 'button-hover-bg',
			'button_hover_text' => 'button-hover-text',
		);
		$style = '';

		foreach ( $properties as $setting => $property ) {
			$value = IMFW_Settings::sanitize( $setting, IMFW_Settings::get( $modal_id, $setting ) );
			if ( '' !== $value ) {
				$style .= '--imfw-' . $property . ':' . $value . ';';
			}
		}

		return $style;
	}

	private function format_content( $content, $modal_id ) {
		/* Avoid the global the_content filter because some page builders replace the supplied content. */
		$content = do_blocks( $content );
		$content = wptexturize( $content );
		$content = convert_smilies( $content );
		$content = wpautop( $content );
		$content = shortcode_unautop( $content );
		$content = do_shortcode( $content );

		/**
		 * Filter formatted modal content before allowed post HTML is enforced.
		 *
		 * @param string $content  Formatted content.
		 * @param int    $modal_id Modal post ID.
		 */
		return apply_filters( 'imfw_modal_content', $content, $modal_id );
	}

	private function browser_settings( $modal_id ) {
		$settings = array(
			'id'         => $modal_id,
			'trigger'    => IMFW_Settings::get( $modal_id, 'trigger' ),
			'delay'      => (int) IMFW_Settings::get( $modal_id, 'delay' ),
			'scroll'     => (int) IMFW_Settings::get( $modal_id, 'scroll' ),
			'selector'   => IMFW_Settings::get( $modal_id, 'selector' ),
			'frequency'  => IMFW_Settings::get( $modal_id, 'frequency' ),
			'repeatDays' => (int) IMFW_Settings::get( $modal_id, 'repeat_days' ),
			'scope'      => IMFW_Settings::get( $modal_id, 'frequency_scope' ),
			'action'     => IMFW_Settings::get( $modal_id, 'confirm_action' ),
			'url'        => IMFW_Settings::get( $modal_id, 'confirm_url' ),
		);

		return apply_filters( 'imfw_browser_settings', $settings, $modal_id );
	}

	public function render() {
		foreach ( $this->modals as $modal ) {
			$modal_id = $modal->ID;
			$classes  = array(
				'imfw-modal',
				'imfw-place-' . sanitize_html_class( IMFW_Settings::get( $modal_id, 'placement' ) ),
				'imfw-anim-' . sanitize_html_class( IMFW_Settings::get( $modal_id, 'animation' ) ),
			);
			$custom_class = IMFW_Settings::get( $modal_id, 'custom_class' );
			if ( $custom_class ) {
				$classes[] = sanitize_html_class( $custom_class );
			}

			$classes = apply_filters( 'imfw_modal_classes', $classes, $modal_id );
			$title   = get_the_title( $modal_id );
			if ( ! $title ) {
				$title = __( 'Information', 'tomawesome-modal-notices' );
			}

			do_action( 'imfw_before_modal', $modal_id );
			?>
			<div
				id="imfw-modal-<?php echo esc_attr( $modal_id ); ?>"
				class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ); ?>"
				style="<?php echo esc_attr( $this->inline_style( $modal_id ) ); ?>"
				data-imfw="<?php echo esc_attr( wp_json_encode( $this->browser_settings( $modal_id ) ) ); ?>"
				hidden
				aria-hidden="true"
			>
				<div class="imfw-overlay" data-imfw-close></div>
				<div class="imfw-dialog" role="dialog" aria-modal="true" aria-labelledby="imfw-title-<?php echo esc_attr( $modal_id ); ?>" tabindex="-1">
					<?php if ( '1' === IMFW_Settings::get( $modal_id, 'close_x' ) ) : ?>
						<button type="button" class="imfw-close" data-imfw-close aria-label="<?php esc_attr_e( 'Close', 'tomawesome-modal-notices' ); ?>">&times;</button>
					<?php endif; ?>
					<h2 id="imfw-title-<?php echo esc_attr( $modal_id ); ?>" class="imfw-sr-only"><?php echo esc_html( $title ); ?></h2>
					<div class="imfw-content"><?php echo wp_kses_post( $this->format_content( $modal->post_content, $modal_id ) ); ?></div>
					<?php $confirm_text = IMFW_Settings::get( $modal_id, 'confirm_text' ); ?>
					<?php if ( $confirm_text ) : ?>
						<button type="button" class="imfw-confirm" data-imfw-confirm><?php echo esc_html( $confirm_text ); ?></button>
					<?php endif; ?>
				</div>
			</div>
			<?php
			do_action( 'imfw_after_modal', $modal_id );
		}
	}
}
