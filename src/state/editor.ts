import type { EditorState, NavNode, Navigation } from '../types';
import {
	duplicateBranch,
	indentNode,
	moveNode,
	outdentNode,
	removeNodes,
	updateNode,
	validateTree,
} from '../domain/tree';

export type Action =
	| { type: 'SELECT'; ids: string[] }
	| { type: 'TOGGLE_EXPANDED'; id: string }
	| { type: 'SET_FILTER'; value: string }
	| { type: 'SET_FOCUS'; id: string | null }
	| { type: 'SET_VIEWPORT'; viewport: EditorState[ 'viewport' ] }
	| {
			type: 'UPDATE_NODE';
			id: string;
			patch: Partial< NavNode >;
			label: string;
	  }
	| {
			type: 'BULK_UPDATE';
			ids: string[];
			patch: Partial< NavNode >;
			label: string;
	  }
	| { type: 'ADD_NODES'; nodes: NavNode[]; label: string }
	| { type: 'DELETE'; ids: string[]; promote?: boolean }
	| { type: 'DUPLICATE'; id: string }
	| {
			type: 'MOVE';
			id: string;
			targetId: string;
			position: 'before' | 'after' | 'inside';
	  }
	| { type: 'INDENT'; id: string }
	| { type: 'OUTDENT'; id: string }
	| { type: 'UNDO' }
	| { type: 'REDO' }
	| {
			type: 'STATUS';
			status: EditorState[ 'status' ];
			message: string;
			version?: number | null;
	  }
	| { type: 'PUBLISHED'; navigation: Navigation };

export const initialState = (
	navigation: Navigation,
	draftVersion: number | null = null
): EditorState => ( {
	navigation,
	selected: [],
	expanded: new Set( navigation.nodes.map( ( node ) => node.id ) ),
	past: [],
	future: [],
	status: 'saved',
	statusMessage: 'Saved',
	focusRoot: null,
	filter: '',
	viewport: 'desktop',
	draftVersion,
	publishedChecksum: navigation.checksum,
} );

const mutate = (
	state: EditorState,
	nodes: NavNode[],
	label: string
): EditorState => {
	const errors = validateTree( nodes );
	if ( errors.length ) {
		return { ...state, status: 'error', statusMessage: errors[ 0 ] };
	}
	return {
		...state,
		navigation: { ...state.navigation, nodes },
		past: [
			...state.past.slice( -99 ),
			{ nodes: state.navigation.nodes, label },
		],
		future: [],
		status: 'dirty',
		statusMessage: 'Unsaved changes',
	};
};

export function editorReducer(
	state: EditorState,
	action: Action
): EditorState {
	switch ( action.type ) {
		case 'SELECT':
			return { ...state, selected: action.ids };
		case 'TOGGLE_EXPANDED': {
			const next = new Set( state.expanded );
			if ( next.has( action.id ) ) {
				next.delete( action.id );
			} else {
				next.add( action.id );
			}
			return { ...state, expanded: next };
		}
		case 'SET_FILTER':
			return { ...state, filter: action.value };
		case 'SET_FOCUS':
			return { ...state, focusRoot: action.id };
		case 'SET_VIEWPORT':
			return { ...state, viewport: action.viewport };
		case 'UPDATE_NODE':
			return mutate(
				state,
				updateNode( state.navigation.nodes, action.id, action.patch ),
				action.label
			);
		case 'BULK_UPDATE':
			return mutate(
				state,
				state.navigation.nodes.map( ( node ) =>
					action.ids.includes( node.id )
						? { ...node, ...action.patch }
						: node
				),
				action.label
			);
		case 'ADD_NODES':
			return mutate(
				state,
				[ ...state.navigation.nodes, ...action.nodes ],
				action.label
			);
		case 'DELETE':
			return {
				...mutate(
					state,
					removeNodes(
						state.navigation.nodes,
						action.ids,
						action.promote
					),
					`Deleted ${ action.ids.length } item(s)`
				),
				selected: [],
			};
		case 'DUPLICATE':
			return mutate(
				state,
				duplicateBranch( state.navigation.nodes, action.id ),
				'Duplicated branch'
			);
		case 'MOVE':
			return mutate(
				state,
				moveNode(
					state.navigation.nodes,
					action.id,
					action.targetId,
					action.position
				),
				'Moved item'
			);
		case 'INDENT':
			return mutate(
				state,
				indentNode( state.navigation.nodes, action.id ),
				'Increased item depth'
			);
		case 'OUTDENT':
			return mutate(
				state,
				outdentNode( state.navigation.nodes, action.id ),
				'Decreased item depth'
			);
		case 'UNDO': {
			const previous = state.past[ state.past.length - 1 ];
			if ( ! previous ) {
				return state;
			}
			return {
				...state,
				navigation: { ...state.navigation, nodes: previous.nodes },
				past: state.past.slice( 0, -1 ),
				future: [
					{ nodes: state.navigation.nodes, label: previous.label },
					...state.future,
				],
				status: 'dirty',
				statusMessage: `Undid ${ previous.label }`,
			};
		}
		case 'REDO': {
			const next = state.future[ 0 ];
			if ( ! next ) {
				return state;
			}
			return {
				...state,
				navigation: { ...state.navigation, nodes: next.nodes },
				past: [
					...state.past,
					{ nodes: state.navigation.nodes, label: next.label },
				],
				future: state.future.slice( 1 ),
				status: 'dirty',
				statusMessage: `Redid ${ next.label }`,
			};
		}
		case 'STATUS':
			return {
				...state,
				status: action.status,
				statusMessage: action.message,
				draftVersion:
					action.version === undefined
						? state.draftVersion
						: action.version,
			};
		case 'PUBLISHED':
			return {
				...initialState( action.navigation ),
				statusMessage: 'Published',
				publishedChecksum: action.navigation.checksum,
			};
		default:
			return state;
	}
}
