import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Dashboard } from './components/Dashboard';
import { Editor } from './components/Editor';
import { UtilityPage } from './components/UtilityPages';

export default function App() {
	const params = new URLSearchParams( window.location.search );
	const [ page, setPage ] = useState( params.get( 'view' ) ?? 'menus' );
	const [ editing, setEditing ] = useState< string | null >(
		params.get( 'menu' )
	);
	const navigate = ( next: string ) => {
		setPage( next );
		setEditing( null );
		history.replaceState(
			{},
			'',
			`${ window.location.pathname }?page=navigation-studio&view=${ next }`
		);
	};
	const edit = ( key: string ) => {
		setEditing( key );
		history.replaceState(
			{},
			'',
			`${ window.location.pathname }?page=navigation-studio&view=menus&menu=${ encodeURIComponent( key ) }`
		);
	};
	if ( editing ) {
		return (
			<Editor
				menuKey={ editing }
				onBack={ () => {
					setEditing( null );
					history.replaceState(
						{},
						'',
						`${ window.location.pathname }?page=navigation-studio`
					);
				} }
			/>
		);
	}
	return (
		<div className="navstudio-app">
			<nav
				className="navstudio-primary-nav"
				aria-label={ __(
					'Navigation Studio sections',
					'navigation-studio'
				) }
			>
				{ [
					[ 'menus', __( 'Menus', 'navigation-studio' ) ],
					[ 'templates', __( 'Templates', 'navigation-studio' ) ],
					[
						'transfer',
						__( 'Import / Export', 'navigation-studio' ),
					],
					[ 'settings', __( 'Settings', 'navigation-studio' ) ],
					[
						'integrations',
						__( 'Integrations', 'navigation-studio' ),
					],
					[ 'diagnostics', __( 'Diagnostics', 'navigation-studio' ) ],
				].map( ( [ value, label ] ) => (
					<button
						key={ value }
						type="button"
						className={ page === value ? 'is-active' : '' }
						aria-current={ page === value ? 'page' : undefined }
						onClick={ () => navigate( value ) }
					>
						{ label }
					</button>
				) ) }
			</nav>
			{ page === 'menus' ? (
				<Dashboard onEdit={ edit } />
			) : (
				<UtilityPage page={ page } onEdit={ edit } />
			) }
		</div>
	);
}
