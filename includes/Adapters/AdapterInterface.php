<?php
/**
 * Native navigation adapter contract.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Adapters;

use NavigationStudio\Domain\Navigation;

defined( 'ABSPATH' ) || exit;

interface AdapterInterface {
	public function supports( string $source_type ): bool;
	public function read( int $source_id ): Navigation;
	public function publish( Navigation $navigation ): Navigation;
	/** @return array<int,array<string,mixed>> */
	public function list(): array;
}
