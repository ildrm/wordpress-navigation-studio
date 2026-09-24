import { createNode } from '../../src/domain/tree';
import { editorReducer, initialState } from '../../src/state/editor';
import type { Navigation } from '../../src/types';

const navigation: Navigation = {
	key: 'classic:1',
	name: 'Main',
	sourceType: 'classic',
	sourceId: 1,
	nodes: [ createNode( { id: 'aaaaaa', label: 'Home' } ) ],
	settings: {},
	checksum: 'published',
};

describe( 'editor history', () => {
	it( 'records, undoes, and redoes a user-visible mutation', () => {
		const initial = initialState( navigation );
		const changed = editorReducer( initial, {
			type: 'UPDATE_NODE',
			id: 'aaaaaa',
			patch: { label: 'Start' },
			label: 'Renamed item',
		} );
		expect( changed.navigation.nodes[ 0 ].label ).toBe( 'Start' );
		expect( changed.status ).toBe( 'dirty' );
		const undone = editorReducer( changed, { type: 'UNDO' } );
		expect( undone.navigation.nodes[ 0 ].label ).toBe( 'Home' );
		const redone = editorReducer( undone, { type: 'REDO' } );
		expect( redone.navigation.nodes[ 0 ].label ).toBe( 'Start' );
	} );

	it( 'does not create history for a rejected structural mutation', () => {
		const initial = initialState( navigation );
		const changed = editorReducer( initial, {
			type: 'MOVE',
			id: 'aaaaaa',
			targetId: 'missing',
			position: 'after',
		} );
		expect( changed ).toBe( initial );
	} );
} );
