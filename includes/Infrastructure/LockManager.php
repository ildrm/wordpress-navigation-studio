<?php
/**
 * Advisory editing locks.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Infrastructure;

defined( 'ABSPATH' ) || exit;

final class LockManager {
	private const TTL = 120;
	/** @return array<string,mixed> */
	public function acquire( string $key, int $user_id ): array {
		$option = 'navstudio_lock_' . md5( $key );
		$lock   = get_transient( $option );
		if ( is_array( $lock ) && (int) $lock['userId'] !== $user_id ) {
			$user = get_userdata( (int) $lock['userId'] );
			return array(
				'owned'     => false,
				'userId'    => (int) $lock['userId'],
				'userName'  => $user ? $user->display_name : __( 'Another user', 'navigation-studio' ),
				'expiresAt' => (int) $lock['expiresAt'],
			);
		}
		$lock = array(
			'owned'     => true,
			'userId'    => $user_id,
			'expiresAt' => time() + self::TTL,
		);
		set_transient( $option, $lock, self::TTL );
		return $lock;
	}
	public function release( string $key, int $user_id ): void {
		$option = 'navstudio_lock_' . md5( $key );
		$lock   = get_transient( $option );
		if ( is_array( $lock ) && (int) $lock['userId'] === $user_id ) {
			delete_transient( $option ); }
	}
}
