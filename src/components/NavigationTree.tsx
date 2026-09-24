/* eslint-disable no-nested-ternary -- Compact tree glyphs have three mutually exclusive visual states. */
/* eslint-disable jsx-a11y/no-autofocus -- Inline editing is explicitly invoked and focus must move into the controlled input. */
import {
	Button,
	Modal,
	SearchControl,
	SelectControl,
} from '@wordpress/components';
import { useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { depthOf, descendants, visibleNodes } from '../domain/tree';
import type { Action } from '../state/editor';
import type { EditorState, NavNode } from '../types';

export function NavigationTree( {
	state,
	dispatch,
}: {
	state: EditorState;
	dispatch: React.Dispatch< Action >;
} ) {
	const [ editing, setEditing ] = useState< string | null >( null );
	const [ moveId, setMoveId ] = useState< string | null >( null );
	const [ moveTarget, setMoveTarget ] = useState( '' );
	const [ movePosition, setMovePosition ] = useState<
		'before' | 'after' | 'inside'
	>( 'after' );
	const [ liveMessage, setLiveMessage ] = useState( '' );
	const lastSelected = useRef< string | null >( null );
	const query = state.filter.toLocaleLowerCase();
	const base = visibleNodes(
		state.navigation.nodes,
		state.expanded,
		state.focusRoot
	);
	const nodes = useMemo( () => {
		if ( ! query ) {
			return base;
		}
		const matching = new Set< string >();
		state.navigation.nodes.forEach( ( node ) => {
			const haystack =
				`${ node.label } ${ node.url } ${ node.type } ${ node.attributes.className ?? '' }`.toLocaleLowerCase();
			if ( haystack.includes( query ) ) {
				matching.add( node.id );
				let parent = node.parentId;
				while ( parent ) {
					matching.add( parent );
					parent =
						state.navigation.nodes.find(
							( item ) => item.id === parent
						)?.parentId ?? null;
				}
			}
		} );
		return base.filter( ( node ) => matching.has( node.id ) );
	}, [ base, query, state.navigation.nodes ] );

	const select = ( event: React.MouseEvent, node: NavNode ) => {
		let selected = [ node.id ];
		if ( event.metaKey || event.ctrlKey ) {
			selected = state.selected.includes( node.id )
				? state.selected.filter( ( id ) => id !== node.id )
				: [ ...state.selected, node.id ];
		}
		if ( event.shiftKey && lastSelected.current ) {
			const start = nodes.findIndex(
				( item ) => item.id === lastSelected.current
			);
			const end = nodes.findIndex( ( item ) => item.id === node.id );
			selected = nodes
				.slice( Math.min( start, end ), Math.max( start, end ) + 1 )
				.map( ( item ) => item.id );
		}
		lastSelected.current = node.id;
		dispatch( { type: 'SELECT', ids: selected } );
	};

	const keyboard = (
		event: React.KeyboardEvent,
		node: NavNode,
		index: number
	) => {
		const modifier = event.ctrlKey || event.metaKey;
		if ( modifier && event.key.toLowerCase() === 'd' ) {
			event.preventDefault();
			dispatch( { type: 'DUPLICATE', id: node.id } );
			setLiveMessage(
				sprintf(
					/* translators: %s is the duplicated navigation label. */
					__( 'Duplicated %s.', 'navigation-studio' ),
					node.label
				)
			);
			return;
		}
		if ( modifier && event.key === 'ArrowLeft' ) {
			event.preventDefault();
			dispatch( { type: 'OUTDENT', id: node.id } );
			setLiveMessage(
				__(
					'Item moved one level toward the root.',
					'navigation-studio'
				)
			);
			return;
		}
		if ( modifier && event.key === 'ArrowRight' ) {
			event.preventDefault();
			dispatch( { type: 'INDENT', id: node.id } );
			setLiveMessage(
				__( 'Item moved one level deeper.', 'navigation-studio' )
			);
			return;
		}
		if ( modifier && event.key === 'ArrowUp' && index > 0 ) {
			event.preventDefault();
			dispatch( {
				type: 'MOVE',
				id: node.id,
				targetId: nodes[ index - 1 ].id,
				position: 'before',
			} );
			return;
		}
		if (
			modifier &&
			event.key === 'ArrowDown' &&
			index < nodes.length - 1
		) {
			event.preventDefault();
			dispatch( {
				type: 'MOVE',
				id: node.id,
				targetId: nodes[ index + 1 ].id,
				position: 'after',
			} );
			return;
		}
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			setEditing( node.id );
		}
		if ( event.key === 'Delete' && state.selected.length ) {
			event.preventDefault();
			dispatch( { type: 'DELETE', ids: state.selected } );
			setLiveMessage(
				__(
					'Selected items deleted. Use Undo to restore them.',
					'navigation-studio'
				)
			);
		}
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			const next = event.key === 'ArrowDown' ? index + 1 : index - 1;
			document
				.getElementById( `navstudio-node-${ nodes[ next ]?.id }` )
				?.focus();
		}
		if (
			event.key === 'ArrowRight' &&
			descendants( state.navigation.nodes, node.id ).length &&
			! state.expanded.has( node.id )
		) {
			dispatch( { type: 'TOGGLE_EXPANDED', id: node.id } );
		}
		if ( event.key === 'ArrowLeft' && state.expanded.has( node.id ) ) {
			dispatch( { type: 'TOGGLE_EXPANDED', id: node.id } );
		}
	};

	return (
		<section
			className="navstudio-panel navstudio-tree-panel"
			aria-labelledby="tree-heading"
		>
			<header>
				<div>
					<h2 id="tree-heading">
						{ __( 'Navigation structure', 'navigation-studio' ) }
					</h2>
					{ state.focusRoot && (
						<Button
							variant="link"
							onClick={ () =>
								dispatch( { type: 'SET_FOCUS', id: null } )
							}
						>
							{ __(
								'Return to full navigation',
								'navigation-studio'
							) }
						</Button>
					) }
				</div>
				<div className="navstudio-tree-actions">
					<Button
						size="small"
						onClick={ () =>
							dispatch( {
								type: 'SELECT',
								ids: nodes.map( ( node ) => node.id ),
							} )
						}
					>
						{ __( 'Select visible', 'navigation-studio' ) }
					</Button>
					<Button
						size="small"
						onClick={ () =>
							dispatch( { type: 'SELECT', ids: [] } )
						}
					>
						{ __( 'Clear', 'navigation-studio' ) }
					</Button>
				</div>
			</header>
			<div className="navstudio-panel__controls">
				<SearchControl
					label={ __( 'Search navigation', 'navigation-studio' ) }
					value={ state.filter }
					onChange={ ( value ) =>
						dispatch( { type: 'SET_FILTER', value } )
					}
				/>
			</div>
			<div
				role="tree"
				aria-multiselectable="true"
				className="navstudio-tree"
			>
				{ nodes.length === 0 ? (
					<div className="navstudio-empty">
						<h3>
							{ __(
								'This navigation is empty',
								'navigation-studio'
							) }
						</h3>
						<p>
							{ __(
								'Add content from the library or create a custom link.',
								'navigation-studio'
							) }
						</p>
					</div>
				) : (
					nodes.map( ( node, index ) => {
						const selected = state.selected.includes( node.id );
						const hasChildren = state.navigation.nodes.some(
							( child ) => child.parentId === node.id
						);
						return (
							<div
								key={ node.id }
								className={ `navstudio-tree-row${ selected ? ' is-selected' : '' }` }
								style={
									{
										'--depth': depthOf(
											state.navigation.nodes,
											node.id
										),
									} as React.CSSProperties
								}
							>
								<div
									className="navstudio-drop-target is-before"
									onDragOver={ ( event ) =>
										event.preventDefault()
									}
									onDrop={ ( event ) =>
										dispatch( {
											type: 'MOVE',
											id: event.dataTransfer.getData(
												'text/navstudio-node'
											),
											targetId: node.id,
											position: 'before',
										} )
									}
									aria-hidden="true"
								/>
								<div
									id={ `navstudio-node-${ node.id }` }
									role="treeitem"
									aria-level={
										depthOf(
											state.navigation.nodes,
											node.id
										) + 1
									}
									aria-selected={ selected }
									aria-expanded={
										hasChildren
											? state.expanded.has( node.id )
											: undefined
									}
									tabIndex={
										selected ||
										( ! state.selected.length &&
											index === 0 )
											? 0
											: -1
									}
									draggable
									onDragStart={ ( event ) =>
										event.dataTransfer.setData(
											'text/navstudio-node',
											node.id
										)
									}
									onClick={ ( event ) =>
										select( event, node )
									}
									onDoubleClick={ () =>
										setEditing( node.id )
									}
									onKeyDown={ ( event ) =>
										keyboard( event, node, index )
									}
								>
									<span
										className="navstudio-connector"
										aria-hidden="true"
									/>
									<span
										className="navstudio-drag"
										aria-label={ __(
											'Drag item',
											'navigation-studio'
										) }
									>
										⋮⋮
									</span>
									<Button
										className="navstudio-expand"
										size="small"
										variant="tertiary"
										disabled={ ! hasChildren }
										aria-label={
											state.expanded.has( node.id )
												? __(
														'Collapse branch',
														'navigation-studio'
													)
												: __(
														'Expand branch',
														'navigation-studio'
													)
										}
										onClick={ (
											event: React.MouseEvent
										) => {
											event.stopPropagation();
											dispatch( {
												type: 'TOGGLE_EXPANDED',
												id: node.id,
											} );
										} }
									>
										{ hasChildren
											? state.expanded.has( node.id )
												? '▾'
												: '▸'
											: '·' }
									</Button>
									<span
										className="navstudio-type-icon"
										aria-hidden="true"
									>
										{ node.type === 'custom' ? '↗' : '◫' }
									</span>
									{ editing === node.id ? (
										<input
											className="navstudio-inline-edit"
											autoFocus
											defaultValue={ node.label }
											aria-label={ __(
												'Navigation label',
												'navigation-studio'
											) }
											onBlur={ ( event ) => {
												dispatch( {
													type: 'UPDATE_NODE',
													id: node.id,
													patch: {
														label: event
															.currentTarget
															.value,
													},
													label: __(
														'Renamed item',
														'navigation-studio'
													),
												} );
												setEditing( null );
											} }
											onKeyDown={ ( event ) => {
												if ( event.key === 'Enter' ) {
													event.currentTarget.blur();
												}
												if ( event.key === 'Escape' ) {
													setEditing( null );
												}
												event.stopPropagation();
											} }
										/>
									) : (
										<span className="navstudio-node-label">
											{ node.label ||
												__(
													'Untitled item',
													'navigation-studio'
												) }
										</span>
									) }
									<span className="navstudio-node-type">
										{ node.objectType }
									</span>
									<div className="navstudio-row-actions">
										<Button
											size="small"
											variant="tertiary"
											onClick={ (
												event: React.MouseEvent
											) => {
												event.stopPropagation();
												setMoveId( node.id );
												setMoveTarget(
													state.navigation.nodes.find(
														( other ) =>
															other.id !== node.id
													)?.id ?? ''
												);
											} }
										>
											{ __(
												'Move',
												'navigation-studio'
											) }
										</Button>
										<Button
											size="small"
											variant="tertiary"
											onClick={ (
												event: React.MouseEvent
											) => {
												event.stopPropagation();
												dispatch( {
													type: 'DUPLICATE',
													id: node.id,
												} );
											} }
										>
											{ __(
												'Duplicate',
												'navigation-studio'
											) }
										</Button>
									</div>
								</div>
								<div
									className="navstudio-drop-target is-inside"
									onDragOver={ ( event ) =>
										event.preventDefault()
									}
									onDrop={ ( event ) =>
										dispatch( {
											type: 'MOVE',
											id: event.dataTransfer.getData(
												'text/navstudio-node'
											),
											targetId: node.id,
											position: 'inside',
										} )
									}
									aria-hidden="true"
								/>
								<div
									className="navstudio-drop-target is-after"
									onDragOver={ ( event ) =>
										event.preventDefault()
									}
									onDrop={ ( event ) =>
										dispatch( {
											type: 'MOVE',
											id: event.dataTransfer.getData(
												'text/navstudio-node'
											),
											targetId: node.id,
											position: 'after',
										} )
									}
									aria-hidden="true"
								/>
							</div>
						);
					} )
				) }
			</div>
			{ state.selected.length > 0 && (
				<footer className="navstudio-bulk">
					<strong>
						{ sprintf(
							/* translators: %d is the number of selected navigation items. */
							__( '%d selected', 'navigation-studio' ),
							state.selected.length
						) }
					</strong>
					<Button
						isDestructive
						onClick={ () =>
							dispatch( { type: 'DELETE', ids: state.selected } )
						}
					>
						{ __( 'Delete', 'navigation-studio' ) }
					</Button>
					{ state.selected.length === 1 && (
						<Button
							onClick={ () =>
								dispatch( {
									type: 'SET_FOCUS',
									id: state.selected[ 0 ],
								} )
							}
						>
							{ __( 'Focus branch', 'navigation-studio' ) }
						</Button>
					) }
				</footer>
			) }
			<div className="screen-reader-text" aria-live="polite">
				{ liveMessage }
			</div>
			{ moveId && (
				<Modal
					title={ __( 'Move item', 'navigation-studio' ) }
					onRequestClose={ () => setMoveId( null ) }
				>
					<SelectControl
						label={ __( 'Destination item', 'navigation-studio' ) }
						value={ moveTarget }
						onChange={ setMoveTarget }
						options={ state.navigation.nodes
							.filter(
								( node ) =>
									node.id !== moveId &&
									! descendants(
										state.navigation.nodes,
										moveId
									).includes( node.id )
							)
							.map( ( node ) => ( {
								label: node.label,
								value: node.id,
							} ) ) }
					/>
					<SelectControl
						label={ __( 'Position', 'navigation-studio' ) }
						value={ movePosition }
						onChange={ ( value ) =>
							setMovePosition( value as typeof movePosition )
						}
						options={ [
							{
								label: __( 'Before', 'navigation-studio' ),
								value: 'before',
							},
							{
								label: __( 'After', 'navigation-studio' ),
								value: 'after',
							},
							{
								label: __(
									'Inside as last child',
									'navigation-studio'
								),
								value: 'inside',
							},
						] }
					/>
					<div className="navstudio-modal-actions">
						<Button
							variant="primary"
							disabled={ ! moveTarget }
							onClick={ () => {
								dispatch( {
									type: 'MOVE',
									id: moveId,
									targetId: moveTarget,
									position: movePosition,
								} );
								setMoveId( null );
							} }
						>
							{ __( 'Move', 'navigation-studio' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => setMoveId( null ) }
						>
							{ __( 'Cancel', 'navigation-studio' ) }
						</Button>
					</div>
				</Modal>
			) }
		</section>
	);
}
