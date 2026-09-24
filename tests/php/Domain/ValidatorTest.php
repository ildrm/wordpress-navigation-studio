<?php
/**
 * Domain validator tests.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Tests\Domain;

use InvalidArgumentException;
use NavigationStudio\Domain\Node;
use NavigationStudio\Domain\Validator;
use WP_UnitTestCase;

final class ValidatorTest extends WP_UnitTestCase {
	public function test_accepts_a_valid_tree(): void {
		$nodes = array(
			new Node(
				array(
					'id'    => 'parent-1',
					'label' => 'Parent',
				)
			),
			new Node(
				array(
					'id'       => 'child-1',
					'parentId' => 'parent-1',
					'label'    => 'Child',
				)
			),
		);
		Validator::assert_valid( $nodes );
		$this->assertCount( 2, $nodes );
	}

	public function test_rejects_a_cycle(): void {
		$this->expectException( InvalidArgumentException::class );
		Validator::assert_valid(
			array(
				new Node(
					array(
						'id'       => 'first-1',
						'parentId' => 'second-2',
					)
				),
				new Node(
					array(
						'id'       => 'second-2',
						'parentId' => 'first-1',
					)
				),
			)
		);
	}

	public function test_rejects_unsafe_url_protocols(): void {
		$this->expectException( InvalidArgumentException::class );
		new Node(
			array(
				'id'  => 'unsafe-1',
				'url' => 'javascript:alert(1)',
			)
		);
	}
}
