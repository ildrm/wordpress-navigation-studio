<?php
/**
 * Deterministic navigation health checks.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Application;

use NavigationStudio\Domain\Navigation;

defined( 'ABSPATH' ) || exit;

final class HealthChecker {
	/** @return array<int,array<string,mixed>> */
	public function check( Navigation $navigation ): array {
		$issues = array();
		$urls   = array();
		$nodes  = array();
		foreach ( $navigation->nodes() as $node ) {
			$nodes[ $node->id() ] = $node; }
		foreach ( $navigation->nodes() as $node ) {
			$data  = $node->jsonSerialize();
			$label = trim( (string) $data['label'] );
			if ( '' === $label ) {
				$issues[] = $this->issue( 'error', 'empty_label', $node->id(), __( 'Item has no accessible label.', 'navigation-studio' ) ); }
			if ( mb_strlen( $label ) > 80 ) {
				$issues[] = $this->issue( 'warning', 'long_label', $node->id(), __( 'Label is unusually long.', 'navigation-studio' ) ); }
			$url = (string) $data['url'];
			if ( $url && isset( $urls[ $url ] ) ) {
				$issues[] = $this->issue( 'info', 'duplicate_url', $node->id(), __( 'Another item has the same destination.', 'navigation-studio' ) ); }
			$urls[ $url ] = true;
			$depth        = 0;
			$parent       = $node->parent_id();
			while ( $parent && isset( $nodes[ $parent ] ) ) {
				++$depth;
				$parent = $nodes[ $parent ]->parent_id(); }
			if ( $depth > 3 ) {
				$issues[] = $this->issue( 'warning', 'deep_nesting', $node->id(), __( 'Deep nesting may be difficult to use.', 'navigation-studio' ) ); }
			if ( $data['objectId'] && ! $this->source_exists( $data ) ) {
				$issues[] = $this->issue( 'error', 'missing_source', $node->id(), __( 'Linked source content is unavailable.', 'navigation-studio' ) ); }
		}
		return $issues;
	}

	/** @param array<string,mixed> $data Node data. */
	private function source_exists( array $data ): bool {
		$type        = (string) $data['type'];
		$object_type = (string) $data['objectType'];
		$object_id   = (int) $data['objectId'];
		if ( 'custom' === $type || 'block-unsupported' === $type ) {
			return true;
		}
		if ( 'taxonomy' === $type || ( 'block' === $type && 'taxonomy' === $object_type ) ) {
			$term = get_term( $object_id );
			return ! is_wp_error( $term ) && ! empty( $term );
		}
		if ( 'post_type_archive' === $type ) {
			return null !== get_post_type_object( $object_type );
		}
		return null !== get_post( $object_id );
	}

	/** @return array<string,mixed> */
	private function issue( string $severity, string $code, string $node_id, string $message ): array {
		return array(
			'severity' => $severity,
			'code'     => $code,
			'node_id'  => $node_id,
			'message'  => $message,
		);
	}
}
