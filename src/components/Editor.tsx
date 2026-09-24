/* eslint-disable no-nested-ternary -- Save state maps two independent booleans to three explicit statuses. */
import { Notice, Spinner } from '@wordpress/components';
import {
	useCallback,
	useEffect,
	useMemo,
	useReducer,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { editorReducer, initialState } from '../state/editor';
import type { Navigation } from '../types';
import { CommandPalette, type Command } from './CommandPalette';
import { ContentLibrary } from './ContentLibrary';
import { Inspector } from './Inspector';
import { NavigationTree } from './NavigationTree';
import { Preview } from './Preview';
import { EditorToolbar } from './Toolbar';

export function Editor( {
	menuKey,
	onBack,
}: {
	menuKey: string;
	onBack: () => void;
} ) {
	const [ loaded, setLoaded ] = useState< {
		navigation: Navigation;
		draftVersion: number | null;
	} | null >( null );
	const [ loadError, setLoadError ] = useState( '' );
	useEffect( () => {
		Promise.all( [
			api.menu( menuKey ),
			api.draft( menuKey ),
			api.lock( menuKey ),
		] )
			.then( ( [ published, draft, lock ] ) => {
				if ( ! lock.owned ) {
					setLoadError(
						`${ lock.userName ?? __( 'Another user', 'navigation-studio' ) } ${ __( 'is currently editing. You can review the navigation, but publishing may conflict.', 'navigation-studio' ) }`
					);
				}
				const local = sessionStorage.getItem(
					`navstudio:${ menuKey }`
				);
				let navigation = draft?.navigation ?? published;
				if ( local && ! draft ) {
					try {
						navigation = JSON.parse( local ) as Navigation;
					} catch {
						sessionStorage.removeItem( `navstudio:${ menuKey }` );
					}
				}
				setLoaded( {
					navigation: { ...navigation, checksum: published.checksum },
					draftVersion: draft?.version ?? null,
				} );
			} )
			.catch( ( reason ) =>
				setLoadError(
					reason instanceof Error
						? reason.message
						: __(
								'Navigation could not be loaded.',
								'navigation-studio'
							)
				)
			);
		return () => {
			api.unlock( menuKey ).catch( () => undefined );
		};
	}, [ menuKey ] );
	if ( ! loaded ) {
		return (
			<div className="navstudio-centered navstudio-full">
				<Spinner />
				<span>
					{ loadError ||
						__( 'Opening navigation…', 'navigation-studio' ) }
				</span>
			</div>
		);
	}
	return (
		<EditorReady
			key={ menuKey }
			initial={ loaded.navigation }
			draftVersion={ loaded.draftVersion }
			onBack={ onBack }
			initialWarning={ loadError }
		/>
	);
}

function EditorReady( {
	initial,
	draftVersion,
	onBack,
	initialWarning,
}: {
	initial: Navigation;
	draftVersion: number | null;
	onBack: () => void;
	initialWarning: string;
} ) {
	const [ state, dispatch ] = useReducer(
		editorReducer,
		initialState( initial, draftVersion )
	);
	const [ palette, setPalette ] = useState( false );
	const save = useCallback( async () => {
		dispatch( {
			type: 'STATUS',
			status: 'saving',
			message: __( 'Saving…', 'navigation-studio' ),
		} );
		try {
			const draft = await api.saveDraft(
				state.navigation,
				state.draftVersion
			);
			sessionStorage.removeItem( `navstudio:${ state.navigation.key }` );
			dispatch( {
				type: 'STATUS',
				status: 'saved',
				message: __( 'Draft saved', 'navigation-studio' ),
				version: draft.version,
			} );
		} catch ( reason ) {
			sessionStorage.setItem(
				`navstudio:${ state.navigation.key }`,
				JSON.stringify( state.navigation )
			);
			const conflict =
				reason instanceof Error &&
				reason.message.toLocaleLowerCase().includes( 'conflict' );
			dispatch( {
				type: 'STATUS',
				status: conflict
					? 'conflict'
					: navigator.onLine
						? 'error'
						: 'offline',
				message: conflict
					? __(
							'Conflict detected—your local work is safe',
							'navigation-studio'
						)
					: __(
							'Save failed—your work is stored in this browser',
							'navigation-studio'
						),
			} );
		}
	}, [ state.navigation, state.draftVersion ] );
	const publish = useCallback( async () => {
		dispatch( {
			type: 'STATUS',
			status: 'saving',
			message: __( 'Publishing…', 'navigation-studio' ),
		} );
		try {
			const published = await api.publish(
				state.navigation,
				state.publishedChecksum
			);
			sessionStorage.removeItem( `navstudio:${ state.navigation.key }` );
			dispatch( { type: 'PUBLISHED', navigation: published } );
		} catch ( reason ) {
			const conflict =
				reason instanceof Error &&
				reason.message.toLocaleLowerCase().includes( 'changed' );
			dispatch( {
				type: 'STATUS',
				status: conflict ? 'conflict' : 'error',
				message: conflict
					? __(
							'Conflict detected—compare with the published version before retrying',
							'navigation-studio'
						)
					: __(
							'Publish failed—your draft is still safe',
							'navigation-studio'
						),
			} );
		}
	}, [ state.navigation, state.publishedChecksum ] );

	useEffect( () => {
		if ( state.status !== 'dirty' ) {
			return;
		}
		const timer = setTimeout( save, 5000 );
		return () => clearTimeout( timer );
	}, [ state.navigation.nodes, state.status, save ] );
	useEffect( () => {
		const beforeUnload = ( event: BeforeUnloadEvent ) => {
			if (
				state.status === 'dirty' ||
				state.status === 'error' ||
				state.status === 'offline'
			) {
				event.preventDefault();
				event.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', beforeUnload );
		return () => window.removeEventListener( 'beforeunload', beforeUnload );
	}, [ state.status ] );
	useEffect( () => {
		const shortcuts = ( event: KeyboardEvent ) => {
			const modifier = event.ctrlKey || event.metaKey;
			if ( modifier && event.key.toLocaleLowerCase() === 'k' ) {
				event.preventDefault();
				setPalette( true );
			}
			if ( modifier && event.key.toLocaleLowerCase() === 'z' ) {
				event.preventDefault();
				dispatch( { type: event.shiftKey ? 'REDO' : 'UNDO' } );
			}
			if ( modifier && event.key.toLocaleLowerCase() === 's' ) {
				event.preventDefault();
				save();
			}
		};
		window.addEventListener( 'keydown', shortcuts );
		return () => window.removeEventListener( 'keydown', shortcuts );
	}, [ save ] );

	const commands = useMemo< Command[] >(
		() => [
			{
				name: __( 'Save draft', 'navigation-studio' ),
				shortcut: 'Ctrl+S',
				run: save,
			},
			{
				name: __( 'Publish changes', 'navigation-studio' ),
				run: publish,
				disabled: ! window.navStudioSettings.canPublish,
			},
			{
				name: __( 'Undo last change', 'navigation-studio' ),
				shortcut: 'Ctrl+Z',
				run: () => dispatch( { type: 'UNDO' } ),
				disabled: ! state.past.length,
			},
			{
				name: __( 'Redo change', 'navigation-studio' ),
				shortcut: 'Ctrl+Shift+Z',
				run: () => dispatch( { type: 'REDO' } ),
				disabled: ! state.future.length,
			},
			{
				name: __( 'Expand all branches', 'navigation-studio' ),
				run: () =>
					state.navigation.nodes.forEach( ( node ) => {
						if ( ! state.expanded.has( node.id ) ) {
							dispatch( {
								type: 'TOGGLE_EXPANDED',
								id: node.id,
							} );
						}
					} ),
			},
			{
				name: __( 'Clear selection', 'navigation-studio' ),
				run: () => dispatch( { type: 'SELECT', ids: [] } ),
			},
		],
		[
			save,
			publish,
			state.past.length,
			state.future.length,
			state.navigation.nodes,
			state.expanded,
		]
	);

	return (
		<div className="navstudio-editor">
			<EditorToolbar
				state={ state }
				onBack={ onBack }
				onUndo={ () => dispatch( { type: 'UNDO' } ) }
				onRedo={ () => dispatch( { type: 'REDO' } ) }
				onSave={ save }
				onPublish={ publish }
				onPalette={ () => setPalette( true ) }
			/>
			{ initialWarning && (
				<Notice status="warning" isDismissible={ false }>
					{ initialWarning }
				</Notice>
			) }
			<div className="navstudio-workspace">
				<ContentLibrary
					onAdd={ ( nodes ) =>
						dispatch( {
							type: 'ADD_NODES',
							nodes,
							label: __( 'Added items', 'navigation-studio' ),
						} )
					}
				/>
				<NavigationTree state={ state } dispatch={ dispatch } />
				<Inspector state={ state } dispatch={ dispatch } />
			</div>
			<Preview state={ state } dispatch={ dispatch } />
			{ palette && (
				<CommandPalette
					commands={ commands }
					close={ () => setPalette( false ) }
				/>
			) }
		</div>
	);
}
