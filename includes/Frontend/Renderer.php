<?php
/**
 * Progressive frontend enhancements for native menus.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Frontend;

use NavigationStudio\Application\ConditionEngine;

defined( 'ABSPATH' ) || exit;

final class Renderer {
	/** @var bool */ private $needs_assets = false;
	public function register(): void {
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_items' ), 20, 2 );
		add_filter( 'nav_menu_css_class', array( $this, 'item_classes' ), 20, 4 );
		add_filter( 'nav_menu_link_attributes', array( $this, 'link_attributes' ), 20, 4 );
		add_filter( 'nav_menu_submenu_css_class', array( $this, 'submenu_class' ) );
		add_action( 'wp_footer', array( $this, 'assets' ), 1 );
	}

	/**
	 * @param array<int,mixed> $items Native menu items decorated by WordPress.
	 * @param mixed               $args Menu rendering arguments.
	 * @return array<int,mixed>
	 */
	public function filter_items( array $items, $args = null ): array {
		unset( $args );
		$engine = new ConditionEngine();
		$hidden = array();
		foreach ( $items as $item ) {
			$meta = get_post_meta( $item->ID, '_navstudio_meta', true );
			if ( is_array( $meta ) && ! $engine->visible( (array) ( $meta['conditions'] ?? array() ) ) ) {
				$hidden[ $item->ID ] = true; }
			if ( isset( $hidden[ (int) $item->menu_item_parent ] ) ) {
				$hidden[ $item->ID ] = true; }
		}
		if ( $hidden ) {
			$items = array_values(
				array_filter(
					$items,
					static function ( $item ) use ( $hidden ) {
						return ! isset( $hidden[ $item->ID ] );
					}
				)
			); }
		if ( $items && $this->enhanced_enabled() ) {
			$this->needs_assets = true; }
		return $items;
	}

	/**
	 * @param array<string,string> $atts Link attributes.
	 * @param \WP_Post             $item Menu item.
	 * @param mixed                $args Menu arguments.
	 * @param int                  $depth Menu depth.
	 * @return array<string,string>
	 */
	public function link_attributes( array $atts, $item, $args, $depth ): array {
		unset( $args, $depth );
		if ( ! $this->enhanced_enabled() ) {
			return $atts;
		}
		$meta = get_post_meta( $item->ID, '_navstudio_meta', true );
		if ( ! is_array( $meta ) ) {
			return $atts; }
		$attributes = (array) ( $meta['attributes'] ?? array() );
		if ( ! empty( $attributes['ariaLabel'] ) ) {
			$atts['aria-label'] = sanitize_text_field( $attributes['ariaLabel'] );
		}
		$appearance = (array) ( $meta['appearance'] ?? array() );
		if ( ! empty( $appearance['badgeText'] ) ) {
			$atts['data-navstudio-badge'] = sanitize_text_field( $appearance['badgeText'] ); }
		$responsive = (array) ( $meta['responsive'] ?? array() );
		$hidden     = array();
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $viewport ) {
			if ( isset( $responsive[ $viewport ] ) && false === $responsive[ $viewport ] ) {
				$hidden[] = $viewport; }
		}
		if ( $hidden ) {
			$atts['data-navstudio-hidden'] = implode( ' ', $hidden ); }
		return $atts;
	}

	/**
	 * @param array<int,string> $classes Menu item classes.
	 * @param \WP_Post          $item Menu item.
	 * @param mixed             $args Menu arguments.
	 * @param int               $depth Menu depth.
	 * @return array<int,string>
	 */
	public function item_classes( array $classes, $item, $args, $depth ): array {
		unset( $args, $depth );
		if ( ! $this->enhanced_enabled() ) {
			return $classes;
		}
		$meta = get_post_meta( $item->ID, '_navstudio_meta', true );
		if ( ! is_array( $meta ) ) {
			return $classes;
		}
		$responsive = (array) ( $meta['responsive'] ?? array() );
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $viewport ) {
			if ( isset( $responsive[ $viewport ] ) && false === $responsive[ $viewport ] ) {
				$classes[] = 'navstudio-hide-' . $viewport;
			}
		}
		$mega_menu = (array) ( $meta['megaMenu'] ?? array() );
		if ( ! empty( $mega_menu['enabled'] ) ) {
			$columns   = min( 6, max( 2, absint( $mega_menu['columns'] ?? 3 ) ) );
			$classes[] = 'navstudio-mega-menu';
			$classes[] = 'navstudio-mega-menu--columns-' . $columns;
		}
		return array_unique( $classes );
	}

	/**
	 * @param array<int,string> $classes Submenu classes.
	 * @return array<int,string>
	 */
	public function submenu_class( array $classes ): array {
		if ( ! $this->enhanced_enabled() ) {
			return $classes;
		}
		$classes[] = 'navstudio-submenu';
		return array_unique( $classes ); }

	public function assets(): void {
		if ( ! $this->needs_assets ) {
			return; }
		$asset_file = NAVSTUDIO_PATH . 'build/frontend.asset.php';
		$asset      = is_readable( $asset_file ) ? require $asset_file : array(
			'dependencies' => array(),
			'version'      => NAVSTUDIO_VERSION,
		);
		if ( is_readable( NAVSTUDIO_PATH . 'build/frontend.js' ) ) {
			wp_enqueue_script( 'navstudio-frontend', NAVSTUDIO_URL . 'build/frontend.js', $asset['dependencies'], $asset['version'], true ); }
		if ( is_readable( NAVSTUDIO_PATH . 'build/frontend.css' ) ) {
			wp_enqueue_style( 'navstudio-frontend', NAVSTUDIO_URL . ( is_rtl() && is_readable( NAVSTUDIO_PATH . 'build/frontend-rtl.css' ) ? 'build/frontend-rtl.css' : 'build/frontend.css' ), array(), $asset['version'] ); }
	}

	private function enhanced_enabled(): bool {
		$settings = get_option( 'navstudio_settings', array() );
		return is_array( $settings ) && ! empty( $settings['enhancedRendering'] );
	}
}
