<?php
/**
 * Activation integration tests.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Tests\Integration;

use NavigationStudio\Infrastructure\Activator;
use WP_UnitTestCase;

final class ActivationTest extends WP_UnitTestCase {
	public function test_activation_creates_schema_and_capabilities(): void {
		global $wpdb;
		Activator::activate();
		$this->assertSame( NAVSTUDIO_SCHEMA_VERSION, get_option( 'navstudio_schema_version' ) );
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'manage_navigation_studio' ) );
		$this->assertSame( $wpdb->prefix . 'navstudio_drafts', $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'navstudio_drafts' ) ) );
	}
}
