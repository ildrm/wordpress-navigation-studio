import {
	createNode,
	descendants,
	duplicateBranch,
	indentNode,
	moveNode,
	outdentNode,
	removeNodes,
	validateTree,
} from '../../src/domain/tree';
import type { NavNode } from '../../src/types';

const node = ( id: string, parentId: string | null = null ): NavNode => ( {
	...createNode( { id, label: id } ),
	id,
	parentId,
} );

describe( 'navigation tree commands', () => {
	it( 'moves a complete branch without separating descendants', () => {
		const nodes = [
			node( 'aaaaaa' ),
			node( 'bbbbbb', 'aaaaaa' ),
			node( 'cccccc' ),
		];
		const moved = moveNode( nodes, 'aaaaaa', 'cccccc', 'after' );
		expect( moved.map( ( item ) => item.id ) ).toEqual( [
			'cccccc',
			'aaaaaa',
			'bbbbbb',
		] );
		expect( validateTree( moved ) ).toEqual( [] );
	} );

	it( 'refuses to move a parent inside its descendant', () => {
		const nodes = [ node( 'aaaaaa' ), node( 'bbbbbb', 'aaaaaa' ) ];
		expect( moveNode( nodes, 'aaaaaa', 'bbbbbb', 'inside' ) ).toBe( nodes );
	} );

	it( 'indents and outdents using logical siblings', () => {
		const nodes = [ node( 'aaaaaa' ), node( 'bbbbbb' ), node( 'cccccc' ) ];
		const indented = indentNode( nodes, 'bbbbbb' );
		expect(
			indented.find( ( item ) => item.id === 'bbbbbb' )?.parentId
		).toBe( 'aaaaaa' );
		const outdented = outdentNode( indented, 'bbbbbb' );
		expect(
			outdented.find( ( item ) => item.id === 'bbbbbb' )?.parentId
		).toBeNull();
	} );

	it( 'deletes branches or promotes direct children', () => {
		const nodes = [
			node( 'aaaaaa' ),
			node( 'bbbbbb', 'aaaaaa' ),
			node( 'cccccc', 'bbbbbb' ),
		];
		expect(
			removeNodes( nodes, [ 'bbbbbb' ] ).map( ( item ) => item.id )
		).toEqual( [ 'aaaaaa' ] );
		const promoted = removeNodes( nodes, [ 'bbbbbb' ], true );
		expect(
			promoted.find( ( item ) => item.id === 'cccccc' )?.parentId
		).toBe( 'aaaaaa' );
	} );

	it( 'duplicates a branch with new stable IDs', () => {
		const nodes = [ node( 'aaaaaa' ), node( 'bbbbbb', 'aaaaaa' ) ];
		const result = duplicateBranch( nodes, 'aaaaaa' );
		expect( result ).toHaveLength( 4 );
		expect( new Set( result.map( ( item ) => item.id ) ).size ).toBe( 4 );
		expect( descendants( result, result[ 2 ].id ) ).toEqual( [
			result[ 3 ].id,
		] );
	} );

	it( 'detects duplicate IDs, missing parents, and cycles', () => {
		expect(
			validateTree( [ node( 'aaaaaa' ), node( 'aaaaaa' ) ] )
		).toContain( 'Duplicate ID: aaaaaa' );
		expect( validateTree( [ node( 'aaaaaa', 'missing' ) ] ) ).toContain(
			'Missing parent: aaaaaa'
		);
		expect(
			validateTree( [
				node( 'aaaaaa', 'bbbbbb' ),
				node( 'bbbbbb', 'aaaaaa' ),
			] ).some( ( error ) => error.startsWith( 'Cycle:' ) )
		).toBe( true );
	} );
} );
