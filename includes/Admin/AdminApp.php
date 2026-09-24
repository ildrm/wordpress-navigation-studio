<?php
/**
 * WordPress admin application shell.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Admin;

defined( 'ABSPATH' ) || exit;

final class AdminApp {
	private const SLUG               = 'navigation-studio';
	/** @var string */ private $hook = '';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( NAVSTUDIO_FILE ), array( $this, 'action_links' ) );
	}

	public function menu(): void {
		$this->hook = add_menu_page(
			__( 'Navigation Studio', 'navigation-studio' ),
			__( 'Navigation Studio', 'navigation-studio' ),
			'edit_theme_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-networking',
			59
		);
	}

	public function render(): void {
		echo '<div id="navstudio-root" class="navstudio-root">';
		echo '<p class="navstudio-loading">' . esc_html__( 'Loading Navigation Studio…', 'navigation-studio' ) . '</p>';
		echo '</div>';
	}

	public function assets( string $hook ): void {
		if ( $hook !== $this->hook ) {
			return; }
		$asset_file = NAVSTUDIO_PATH . 'build/index.asset.php';
		$asset      = is_readable( $asset_file ) ? require $asset_file : array(
			'dependencies' => array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-hooks', 'wp-i18n' ),
			'version'      => NAVSTUDIO_VERSION,
		);
		wp_enqueue_style( 'wp-components' );
		if ( is_readable( NAVSTUDIO_PATH . 'build/index.css' ) ) {
			wp_enqueue_style( 'navstudio-admin', NAVSTUDIO_URL . ( is_rtl() && is_readable( NAVSTUDIO_PATH . 'build/index-rtl.css' ) ? 'build/index-rtl.css' : 'build/index.css' ), array( 'wp-components' ), $asset['version'] ); }
		wp_enqueue_script( 'navstudio-admin', NAVSTUDIO_URL . 'build/index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'navstudio-admin', 'navigation-studio', NAVSTUDIO_PATH . 'languages' );
		wp_add_inline_script(
			'navstudio-admin',
			'window.navStudioSettings=' . wp_json_encode(
				array(
					'apiRoot'    => esc_url_raw( rest_url( 'navigation-studio/v1/' ) ),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
					'adminUrl'   => admin_url(),
					'siteUrl'    => home_url( '/' ),
					'locale'     => get_user_locale(),
					'isRtl'      => is_rtl(),
					'canPublish' => current_user_can( 'publish_navigation_studio' ) || current_user_can( 'edit_theme_options' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Add the editor shortcut to the plugin row.
	 *
	 * @param array<int,string> $links Existing action links.
	 * @return array<int,string>
	 */
	public function action_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Open Studio', 'navigation-studio' ) . '</a>' );
		return $links;
	}
}
