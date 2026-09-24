/* eslint-disable no-nested-ternary -- Utility routes render one of three mutually exclusive page bodies. */
import { Button, Card, CardBody, Notice, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';

export function UtilityPage( {
	page,
	onEdit,
}: {
	page: string;
	onEdit: ( key: string ) => void;
} ) {
	const [ data, setData ] = useState< unknown >( null );
	const [ file, setFile ] = useState( '' );
	const [ message, setMessage ] = useState( '' );
	useEffect( () => {
		if ( page === 'diagnostics' ) {
			api.diagnostics().then( setData );
		} else if ( page === 'templates' ) {
			api.templates().then( setData );
		} else if ( page === 'settings' ) {
			api.settings().then( setData );
		} else {
			setData( {} );
		}
	}, [ page ] );
	if ( data === null ) {
		return (
			<div className="navstudio-centered navstudio-full">
				<Spinner />
			</div>
		);
	}
	const title: Record< string, string > = {
		templates: __( 'Templates', 'navigation-studio' ),
		transfer: __( 'Import / Export', 'navigation-studio' ),
		settings: __( 'Settings', 'navigation-studio' ),
		integrations: __( 'Integrations', 'navigation-studio' ),
		diagnostics: __( 'Diagnostics', 'navigation-studio' ),
	};
	return (
		<main className="navstudio-utility">
			<h1>{ title[ page ] }</h1>
			{ message && (
				<Notice status="success" onRemove={ () => setMessage( '' ) }>
					{ message }
				</Notice>
			) }
			{ page === 'transfer' ? (
				<Card>
					<CardBody>
						<h2>
							{ __( 'Import navigation', 'navigation-studio' ) }
						</h2>
						<p>
							{ __(
								'Choose a Navigation Studio JSON export. It is validated and previewed before anything is created.',
								'navigation-studio'
							) }
						</p>
						<input
							type="file"
							accept="application/json,.json"
							onChange={ async ( event ) => {
								const selected =
									event.currentTarget.files?.[ 0 ];
								if ( selected ) {
									const text = await selected.text();
									setFile( text );
									const preview =
										await api.importPreview( text );
									setMessage(
										`${ preview.summary.name }: ${ preview.summary.itemCount } items`
									);
								}
							} }
						/>
						<Button
							variant="primary"
							disabled={ ! file }
							onClick={ async () => {
								const navigation = await api.importCommit(
									file,
									'classic'
								);
								onEdit( navigation.key );
							} }
						>
							{ __(
								'Create classic navigation',
								'navigation-studio'
							) }
						</Button>
					</CardBody>
				</Card>
			) : page === 'integrations' ? (
				<div className="navstudio-menu-grid">
					<Card>
						<CardBody>
							<h2>WooCommerce</h2>
							<p>
								{ __(
									'Product content and commerce conditions appear automatically when WooCommerce is active.',
									'navigation-studio'
								) }
							</p>
						</CardBody>
					</Card>
					<Card>
						<CardBody>
							<h2>WPML / Polylang</h2>
							<p>
								{ __(
									'Language context is detected through public integration APIs when available.',
									'navigation-studio'
								) }
							</p>
						</CardBody>
					</Card>
				</div>
			) : (
				<Card>
					<CardBody>
						<pre className="navstudio-diagnostics">
							{ JSON.stringify( data, null, 2 ) }
						</pre>
					</CardBody>
				</Card>
			) }
		</main>
	);
}
