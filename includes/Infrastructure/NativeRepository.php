<?php
/**
 * Unified native navigation repository.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Infrastructure;

use NavigationStudio\Adapters\BlockNavigationAdapter;
use NavigationStudio\Adapters\ClassicMenuAdapter;
use NavigationStudio\Domain\Navigation;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class NativeRepository {
	/** @var array<int,\NavigationStudio\Adapters\AdapterInterface> */ private $adapters;
	public function __construct() {
		$this->adapters = array( new ClassicMenuAdapter(), new BlockNavigationAdapter() ); }
	/** @return array<int,array<string,mixed>> */
	public function all(): array {
		$result = array();
		foreach ( $this->adapters as $adapter ) {
			$result = array_merge( $result, $adapter->list() ); }
		return $result;
	}
	public function get( string $key ): Navigation {
		list( $type, $id ) = $this->parse_key( $key );
		return $this->adapter( $type )->read( $id ); }
	public function publish( Navigation $navigation ): Navigation {
		return $this->adapter( $navigation->source_type() )->publish( $navigation ); }
	private function adapter( string $type ): \NavigationStudio\Adapters\AdapterInterface {
		foreach ( $this->adapters as $adapter ) {
			if ( $adapter->supports( $type ) ) {
				return $adapter;
			}
		} throw new RuntimeException( 'Unsupported navigation type.' ); }
	/** @return array{0:string,1:int} */
	private function parse_key( string $key ): array {
		if ( ! preg_match( '/^(classic|block):(\d+)$/', $key, $m ) ) {
			throw new RuntimeException( 'Invalid navigation key.' );
		} return array( $m[1], (int) $m[2] ); }
}
