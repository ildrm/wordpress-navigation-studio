import {
	Button,
	DropdownMenu,
	Toolbar,
	ToolbarButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import type { EditorState } from '../types';

interface Props {
	state: EditorState;
	onBack: () => void;
	onUndo: () => void;
	onRedo: () => void;
	onSave: () => void;
	onPublish: () => void;
	onPalette: () => void;
}

export function EditorToolbar( {
	state,
	onBack,
	onUndo,
	onRedo,
	onSave,
	onPublish,
	onPalette,
}: Props ) {
	const exportNavigation = async () => {
		const data = await api.exportNavigation( state.navigation.key );
		const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], {
			type: 'application/json',
		} );
		const url = URL.createObjectURL( blob );
		const link = document.createElement( 'a' );
		link.href = url;
		link.download = `${
			state.navigation.name
				.toLocaleLowerCase()
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /(^-|-$)/g, '' ) || 'navigation'
		}.json`;
		document.body.append( link );
		link.click();
		link.remove();
		window.setTimeout( () => URL.revokeObjectURL( url ), 0 );
	};
	return (
		<header className="navstudio-toolbar">
			<div className="navstudio-toolbar__identity">
				<Button variant="tertiary" onClick={ onBack }>
					{ __( 'All navigations', 'navigation-studio' ) }
				</Button>
				<span aria-hidden="true">/</span>
				<strong>{ state.navigation.name }</strong>
				<span
					className={ `navstudio-status is-${ state.status }` }
					role="status"
				>
					{ state.statusMessage }
				</span>
			</div>
			<Toolbar label={ __( 'Editor actions', 'navigation-studio' ) }>
				<ToolbarButton
					disabled={ ! state.past.length }
					onClick={ onUndo }
					shortcut="Ctrl+Z"
				>
					{ __( 'Undo', 'navigation-studio' ) }
				</ToolbarButton>
				<ToolbarButton
					disabled={ ! state.future.length }
					onClick={ onRedo }
					shortcut="Ctrl+Shift+Z"
				>
					{ __( 'Redo', 'navigation-studio' ) }
				</ToolbarButton>
				<ToolbarButton onClick={ onPalette } shortcut="Ctrl+K">
					{ __( 'Commands', 'navigation-studio' ) }
				</ToolbarButton>
			</Toolbar>
			<div className="navstudio-toolbar__publish">
				<Button
					variant="secondary"
					disabled={ state.status === 'saving' }
					onClick={ onSave }
				>
					{ __( 'Save draft', 'navigation-studio' ) }
				</Button>
				<Button
					variant="primary"
					disabled={
						state.status === 'saving' ||
						! window.navStudioSettings.canPublish
					}
					onClick={ onPublish }
				>
					{ __( 'Publish changes', 'navigation-studio' ) }
				</Button>
				<DropdownMenu
					label={ __( 'More actions', 'navigation-studio' ) }
					controls={ [
						{
							title: __( 'Export JSON', 'navigation-studio' ),
							onClick: () => void exportNavigation(),
						},
					] }
				/>
			</div>
		</header>
	);
}
