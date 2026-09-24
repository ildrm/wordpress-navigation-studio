<?php
/**
 * Visibility condition evaluation.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Application;

defined( 'ABSPATH' ) || exit;

final class ConditionEngine {
	/** @param array<int,array<string,mixed>> $conditions */
	public function visible( array $conditions ): bool {
		foreach ( $conditions as $condition ) {
			$type     = (string) ( $condition['type'] ?? '' );
			$operator = (string) ( $condition['operator'] ?? 'is' );
			$value    = $condition['value'] ?? null;
			$actual   = $this->evaluate( $type, $value );
			$matches  = 'is_not' === $operator ? ! $actual : $actual;
			if ( ! $matches ) {
				return false; }
		}
		return (bool) apply_filters( 'navstudio_conditions_visible', true, $conditions );
	}

	/** @param mixed $value Expected condition value. */
	private function evaluate( string $type, $value ): bool {
		switch ( $type ) {
			case 'logged_in':
				return is_user_logged_in() === (bool) $value;
			case 'role':
				$user = wp_get_current_user();
				return (bool) array_intersect( (array) $value, (array) $user->roles );
			case 'capability':
				return current_user_can( sanitize_key( (string) $value ) );
			case 'front_page':
				return is_front_page() === (bool) $value;
			case 'post_type':
				return is_singular( sanitize_key( (string) $value ) );
			case 'path':
				$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
				$current_path = wp_parse_url( $request_uri, PHP_URL_PATH );
				return fnmatch( (string) $value, $current_path ? $current_path : '/' );
			case 'language':
				return determine_locale() === (string) $value;
			default:
				return (bool) apply_filters( 'navstudio_condition_evaluate', false, $type, $value );
		}
	}
}
