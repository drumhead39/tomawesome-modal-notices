<?php
/**
 * Modal setting defaults, validation, and sanitization.
 *
 * @package TomAwesomeModalNotices
 */

defined( 'ABSPATH' ) || exit;

class IMFW_Settings {
	/**
	 * Return all setting defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		$defaults = array(
			'enabled'         => '1',
			'trigger'         => 'load',
			'delay'           => 1000,
			'scroll'          => 50,
			'selector'        => '',
			'placement'       => 'center',
			'animation'       => 'fade',
			'close_x'         => '1',
			'confirm_text'    => __( 'Confirm', 'tomawesome-modal-notices' ),
			'confirm_action'  => 'close',
			'confirm_url'     => '',
			'frequency'       => 'always',
			'repeat_days'     => 7,
			'frequency_scope' => 'site',
			'start'           => '',
			'end'             => '',
			'target_mode'     => 'sitewide',
			'post_types'      => array( 'page' ),
			'include_ids'     => '',
			'exclude_ids'     => '',
			'tax_rules'       => '',
			'width'           => '90%',
			'max_width'       => '600px',
			'padding'         => '28px',
			'radius'          => '8px',
			'bg'              => '#ffffff',
			'text_color'      => '#222222',
			'font_size'       => '16px',
			'font_family'     => 'inherit',
			'overlay'         => 'rgba(0,0,0,.55)',
			'button_bg'       => '#2271b1',
			'button_text'     => '#ffffff',
			'button_border'   => '#2271b1',
			'button_hover_bg' => '#135e96',
			'button_hover_text' => '#ffffff',
			'custom_class'    => '',
		);

		/**
		 * Filter the defaults used for new modals.
		 *
		 * @param array<string, mixed> $defaults Setting defaults.
		 */
		return apply_filters( 'imfw_setting_defaults', $defaults );
	}

	/**
	 * Get one saved setting with its default fallback.
	 *
	 * @param int    $modal_id Modal post ID.
	 * @param string $key      Setting key.
	 * @return mixed
	 */
	public static function get( $modal_id, $key ) {
		$defaults = self::defaults();
		$default  = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : '';
		$value    = get_post_meta( $modal_id, '_imfw_' . $key, true );

		return '' === $value ? $default : $value;
	}

	/**
	 * Sanitize a setting according to its expected type.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Submitted value.
	 * @return mixed
	 */
	public static function sanitize( $key, $value ) {
		$choices = array(
			'trigger'         => array( 'load', 'delay', 'scroll', 'exit', 'click' ),
			'placement'       => array( 'center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right' ),
			'animation'       => array( 'none', 'fade', 'scale', 'slide-up', 'slide-down', 'slide-left', 'slide-right' ),
			'confirm_action'  => array( 'close', 'url', 'url_new' ),
			'frequency'       => array( 'always', 'session', 'once', 'days' ),
			'frequency_scope' => array( 'site', 'page' ),
			'target_mode'     => array( 'sitewide', 'homepage', 'singular', 'post_types', 'taxonomy', 'archives' ),
		);

		if ( isset( $choices[ $key ] ) ) {
			$value = sanitize_key( (string) $value );
			return in_array( $value, $choices[ $key ], true ) ? $value : self::defaults()[ $key ];
		}

		if ( in_array( $key, array( 'enabled', 'close_x' ), true ) ) {
			return empty( $value ) ? '0' : '1';
		}

		if ( 'delay' === $key ) {
			return min( 3600000, absint( $value ) );
		}

		if ( 'scroll' === $key ) {
			return min( 100, max( 1, absint( $value ) ) );
		}

		if ( 'repeat_days' === $key ) {
			return min( 3650, max( 1, absint( $value ) ) );
		}

		if ( 'post_types' === $key ) {
			$post_types = array_map( 'sanitize_key', (array) $value );
			return array_values( array_filter( $post_types, 'post_type_exists' ) );
		}

		if ( in_array( $key, array( 'include_ids', 'exclude_ids' ), true ) ) {
			$ids = array_unique( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
			return implode( ',', $ids );
		}

		if ( 'confirm_url' === $key ) {
			return esc_url_raw( (string) $value, array( 'http', 'https' ) );
		}

		if ( in_array( $key, array( 'start', 'end' ), true ) ) {
			$value = sanitize_text_field( (string) $value );
			return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ? $value : '';
		}

		if ( 'custom_class' === $key ) {
			return sanitize_html_class( (string) $value );
		}

		if ( 'tax_rules' === $key ) {
			return self::sanitize_taxonomy_rules( $value );
		}

		if ( in_array( $key, array( 'width', 'max_width', 'padding', 'radius', 'font_size', 'font_family' ), true ) ) {
			return self::sanitize_css_value( $value );
		}

		if ( in_array( $key, array( 'bg', 'text_color', 'overlay', 'button_bg', 'button_text', 'button_border', 'button_hover_bg', 'button_hover_text' ), true ) ) {
			return self::sanitize_color( $value );
		}

		return sanitize_text_field( (string) $value );
	}

	private static function sanitize_taxonomy_rules( $value ) {
		$clean = array();
		$lines = preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( (string) $value ) );

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( ':', $line, 2 ) );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$taxonomy = sanitize_key( $parts[0] );
			$terms    = array_filter( array_map( 'sanitize_title', explode( ',', $parts[1] ) ) );
			if ( $taxonomy && $terms ) {
				$clean[] = $taxonomy . ':' . implode( ',', $terms );
			}
		}

		return implode( "\n", $clean );
	}

	private static function sanitize_css_value( $value ) {
		$value = sanitize_text_field( (string) $value );
		return preg_match( '/^[a-zA-Z0-9\s.,%()\-"\']+$/', $value ) ? $value : '';
	}

	private static function sanitize_color( $value ) {
		$value = sanitize_text_field( (string) $value );
		$valid = '/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9.,\s%]+\)|hsla?\([0-9.,\s%]+\)|transparent|currentColor)$/';
		return preg_match( $valid, $value ) ? $value : '';
	}
}
