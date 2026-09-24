import type { NavNode } from '../types';

export const makeId = (): string =>
	globalThis.crypto?.randomUUID?.() ??
	`node-${ Date.now() }-${ Math.random().toString( 36 ).slice( 2 ) }`;

export function descendants( nodes: NavNode[], id: string ): string[] {
	const result: string[] = [];
	const visit = ( parent: string ) => {
		nodes
			.filter( ( node ) => node.parentId === parent )
			.forEach( ( node ) => {
				result.push( node.id );
				visit( node.id );
			} );
	};
	visit( id );
	return result;
}

export function depthOf( nodes: NavNode[], id: string ): number {
	const byId = new Map( nodes.map( ( node ) => [ node.id, node ] ) );
	let depth = 0;
	let current = byId.get( id );
	while ( current?.parentId && depth <= nodes.length ) {
		depth++;
		current = byId.get( current.parentId );
	}
	return depth;
}

export function validateTree( nodes: NavNode[] ): string[] {
	const errors: string[] = [];
	const ids = new Set< string >();
	for ( const node of nodes ) {
		if ( ids.has( node.id ) ) {
			errors.push( `Duplicate ID: ${ node.id }` );
		}
		ids.add( node.id );
	}
	for ( const node of nodes ) {
		if ( node.parentId && ! ids.has( node.parentId ) ) {
			errors.push( `Missing parent: ${ node.id }` );
		}
		if ( node.parentId === node.id ) {
			errors.push( `Self parent: ${ node.id }` );
		}
		const seen = new Set( [ node.id ] );
		let cursor = node.parentId;
		while ( cursor ) {
			if ( seen.has( cursor ) ) {
				errors.push( `Cycle: ${ node.id }` );
				break;
			}
			seen.add( cursor );
			cursor =
				nodes.find( ( candidate ) => candidate.id === cursor )
					?.parentId ?? null;
		}
	}
	return errors;
}

export function visibleNodes(
	nodes: NavNode[],
	expanded: Set< string >,
	focusRoot: string | null
): NavNode[] {
	const focusSet = focusRoot
		? new Set( [ focusRoot, ...descendants( nodes, focusRoot ) ] )
		: null;
	return nodes.filter( ( node ) => {
		if ( focusSet && ! focusSet.has( node.id ) ) {
			return false;
		}
		let parent = node.parentId;
		while ( parent ) {
			if ( focusRoot && parent === focusRoot ) {
				return true;
			}
			if ( ! expanded.has( parent ) ) {
				return false;
			}
			parent =
				nodes.find( ( candidate ) => candidate.id === parent )
					?.parentId ?? null;
		}
		return true;
	} );
}

export function updateNode(
	nodes: NavNode[],
	id: string,
	patch: Partial< NavNode >
): NavNode[] {
	return nodes.map( ( node ) =>
		node.id === id ? mergeNode( node, patch ) : node
	);
}

export function mergeNode( node: NavNode, patch: Partial< NavNode > ): NavNode {
	return {
		...node,
		...patch,
		attributes: patch.attributes
			? { ...node.attributes, ...patch.attributes }
			: node.attributes,
		appearance: patch.appearance
			? { ...node.appearance, ...patch.appearance }
			: node.appearance,
		responsive: patch.responsive
			? { ...node.responsive, ...patch.responsive }
			: node.responsive,
		dynamic: patch.dynamic
			? { ...node.dynamic, ...patch.dynamic }
			: node.dynamic,
		megaMenu: patch.megaMenu
			? { ...node.megaMenu, ...patch.megaMenu }
			: node.megaMenu,
		source: patch.source
			? { ...node.source, ...patch.source }
			: node.source,
	};
}

export function removeNodes(
	nodes: NavNode[],
	ids: string[],
	promoteChildren = false
): NavNode[] {
	const remove = new Set( ids );
	if ( ! promoteChildren ) {
		ids.forEach( ( id ) =>
			descendants( nodes, id ).forEach( ( child ) => remove.add( child ) )
		);
	}
	return nodes
		.filter( ( node ) => ! remove.has( node.id ) )
		.map( ( node ) => {
			if (
				promoteChildren &&
				node.parentId &&
				remove.has( node.parentId )
			) {
				const removedParent = nodes.find(
					( item ) => item.id === node.parentId
				);
				return { ...node, parentId: removedParent?.parentId ?? null };
			}
			return node;
		} );
}

export function moveNode(
	nodes: NavNode[],
	id: string,
	targetId: string,
	position: 'before' | 'after' | 'inside'
): NavNode[] {
	if ( id === targetId || descendants( nodes, id ).includes( targetId ) ) {
		return nodes;
	}
	const moving = nodes.find( ( node ) => node.id === id );
	const target = nodes.find( ( node ) => node.id === targetId );
	if ( ! moving || ! target ) {
		return nodes;
	}
	const branchIds = new Set( [ id, ...descendants( nodes, id ) ] );
	const branch = nodes.filter( ( node ) => branchIds.has( node.id ) );
	const rest = nodes.filter( ( node ) => ! branchIds.has( node.id ) );
	const targetIndex = rest.findIndex( ( node ) => node.id === targetId );
	let insertAt = targetIndex;
	let parentId = target.parentId;
	if ( position === 'inside' ) {
		parentId = target.id;
		insertAt = targetIndex + 1;
		while (
			insertAt < rest.length &&
			descendants( rest, target.id ).includes( rest[ insertAt ].id )
		) {
			insertAt++;
		}
	} else if ( position === 'after' ) {
		insertAt = targetIndex + 1;
		const targetBranch = new Set( descendants( rest, target.id ) );
		while (
			insertAt < rest.length &&
			targetBranch.has( rest[ insertAt ].id )
		) {
			insertAt++;
		}
	}
	branch[ 0 ] = { ...branch[ 0 ], parentId };
	return [
		...rest.slice( 0, insertAt ),
		...branch,
		...rest.slice( insertAt ),
	];
}

export function indentNode( nodes: NavNode[], id: string ): NavNode[] {
	const index = nodes.findIndex( ( node ) => node.id === id );
	if ( index < 1 ) {
		return nodes;
	}
	const current = nodes[ index ];
	for ( let previous = index - 1; previous >= 0; previous-- ) {
		if ( nodes[ previous ].parentId === current.parentId ) {
			return moveNode( nodes, id, nodes[ previous ].id, 'inside' );
		}
	}
	return nodes;
}

export function outdentNode( nodes: NavNode[], id: string ): NavNode[] {
	const current = nodes.find( ( node ) => node.id === id );
	if ( ! current?.parentId ) {
		return nodes;
	}
	return moveNode( nodes, id, current.parentId, 'after' );
}

export function duplicateBranch( nodes: NavNode[], id: string ): NavNode[] {
	const root = nodes.find( ( node ) => node.id === id );
	if ( ! root ) {
		return nodes;
	}
	const ids = new Set( [ id, ...descendants( nodes, id ) ] );
	const branch = nodes.filter( ( node ) => ids.has( node.id ) );
	const idMap = new Map( branch.map( ( node ) => [ node.id, makeId() ] ) );
	const copies = branch.map( ( node, index ) => ( {
		...node,
		id: idMap.get( node.id )!,
		parentId:
			node.id === id
				? root.parentId
				: ( idMap.get( node.parentId! ) ?? root.parentId ),
		label: index === 0 ? `${ node.label } copy` : node.label,
	} ) );
	const after =
		nodes.findIndex(
			( node ) => node.id === branch[ branch.length - 1 ].id
		) + 1;
	return [ ...nodes.slice( 0, after ), ...copies, ...nodes.slice( after ) ];
}

export function createNode(
	item?: Partial< NavNode >,
	parentId: string | null = null
): NavNode {
	return {
		id: makeId(),
		parentId,
		label: item?.label ?? '',
		type: item?.type ?? 'custom',
		objectType: item?.objectType ?? 'custom',
		objectId: item?.objectId ?? 0,
		url: item?.url ?? '',
		description: '',
		attributes: {},
		appearance: {},
		responsive: {},
		conditions: [],
		dynamic: {},
		megaMenu: {},
		source: {},
		...item,
	};
}
