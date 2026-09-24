<?php
/**
 * Tree invariant validation.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Domain;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

final class Validator {
	private const MAX_NODES = 5000;

	/** @param Node[] $nodes */
	public static function assert_valid( array $nodes ): void {
		if ( count( $nodes ) > self::MAX_NODES ) {
			throw new InvalidArgumentException( 'Navigation contains too many nodes.' );
		}
		$parents = array();
		$ordered = array();
		foreach ( $nodes as $node ) {
			if ( array_key_exists( $node->id(), $parents ) ) {
				throw new InvalidArgumentException( 'Duplicate node ID: ' . $node->id() );
			}
			$parents[ $node->id() ] = $node->parent_id();
			if ( null !== $node->parent_id() && ! isset( $ordered[ $node->parent_id() ] ) ) {
				throw new InvalidArgumentException( 'Parents must appear before their descendants.' );
			}
			$ordered[ $node->id() ] = true;
		}

		foreach ( $parents as $id => $parent ) {
			if ( null !== $parent && ! array_key_exists( $parent, $parents ) ) {
				throw new InvalidArgumentException( 'Node references a missing parent: ' . $id );
			}
			$seen   = array( $id => true );
			$cursor = $parent;
			$depth  = 0;
			while ( null !== $cursor ) {
				if ( isset( $seen[ $cursor ] ) ) {
					throw new InvalidArgumentException( 'Navigation trees cannot contain cycles.' );
				}
				$seen[ $cursor ] = true;
				$cursor          = $parents[ $cursor ] ?? null;
				++$depth;
				if ( $depth > 100 ) {
					throw new InvalidArgumentException( 'Navigation depth exceeds the safety limit.' );
				}
			}
		}
	}
}
