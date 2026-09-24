<?php
/**
 * Plugin composition root.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio;

use NavigationStudio\Admin\AdminApp;
use NavigationStudio\Frontend\Renderer;
use NavigationStudio\Infrastructure\DraftRepository;
use NavigationStudio\Infrastructure\LockManager;
use NavigationStudio\Infrastructure\NativeRepository;
use NavigationStudio\Infrastructure\RevisionRepository;
use NavigationStudio\REST\Api;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	/** @var self|null */
	private static $instance;

	/** @var bool */
	private $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( 'navigation-studio', false, dirname( plugin_basename( NAVSTUDIO_FILE ) ) . '/languages' );

		$native    = new NativeRepository();
		$drafts    = new DraftRepository();
		$revisions = new RevisionRepository();
		$locks     = new LockManager();

		( new Api( $native, $drafts, $revisions, $locks ) )->register();
		( new AdminApp() )->register();
		( new Renderer() )->register();

		do_action( 'navstudio_loaded', $this );
	}

	private function __construct() {}
}
