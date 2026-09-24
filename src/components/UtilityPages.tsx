/* eslint-disable no-nested-ternary -- Utility routes render one of several mutually exclusive page bodies. */
import {
	Button,
	Card,
	CardBody,
	Notice,
	RangeControl,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import type { Settings, SourceType, TemplateSummary } from '../types';

const defaultSettings: Settings = {
	enhancedRendering: false,
	revisionRetention: 100,
	deleteDataOnUninstall: false,
};

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
	const [ error, setError ] = useState( '' );
	const [ importType, setImportType ] = useState< SourceType >( 'classic' );
	useEffect( () => {
		let active = true;
		setData( null );
		setError( '' );
		const load = async () => {
			try {
				if ( page === 'diagnostics' ) {
					const result = await api.diagnostics();
					if ( active ) {
						setData( result );
					}
				} else if ( page === 'templates' ) {
					const result = await api.templates();
					if ( active ) {
						setData( result );
					}
				} else if ( page === 'settings' ) {
					const result = await api.settings();
					if ( active ) {
						setData( result );
					}
				} else if ( active ) {
					setData( {} );
				}
			} catch ( reason ) {
				if ( ! active ) {
					return;
				}
				setError(
					reason instanceof Error
						? reason.message
						: __(
								'This page could not be loaded.',
								'navigation-studio'
							)
				);
				setData( {} );
			}
		};
		void load();
		return () => {
			active = false;
		};
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
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ message && (
				<Notice status="success" onRemove={ () => setMessage( '' ) }>
					{ message }
				</Notice>
			) }
			{ page === 'settings' ? (
				<SettingsPage
					initial={ {
						...defaultSettings,
						...( data as Partial< Settings > ),
					} }
					onSaved={ () =>
						setMessage(
							__( 'Settings saved.', 'navigation-studio' )
						)
					}
					onError={ setError }
				/>
			) : page === 'templates' ? (
				<TemplateList
					items={
						( data as { items?: TemplateSummary[] } ).items ?? []
					}
				/>
			) : page === 'transfer' ? (
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
									try {
										const text = await selected.text();
										const preview =
											await api.importPreview( text );
										setFile( text );
										setMessage(
											`${ preview.summary.name }: ${ preview.summary.itemCount } items`
										);
									} catch ( reason ) {
										setFile( '' );
										setError(
											reason instanceof Error
												? reason.message
												: __(
														'Import validation failed.',
														'navigation-studio'
													)
										);
									}
								}
							} }
						/>
						<SelectControl
							label={ __( 'Create as', 'navigation-studio' ) }
							value={ importType }
							onChange={ ( value ) =>
								setImportType( value as SourceType )
							}
							options={ [
								{
									label: __(
										'Classic menu',
										'navigation-studio'
									),
									value: 'classic',
								},
								{
									label: __(
										'Block navigation',
										'navigation-studio'
									),
									value: 'block',
								},
							] }
						/>
						<Button
							variant="primary"
							disabled={ ! file }
							onClick={ async () => {
								try {
									const navigation = await api.importCommit(
										file,
										importType
									);
									onEdit( navigation.key );
								} catch ( reason ) {
									setError(
										reason instanceof Error
											? reason.message
											: __(
													'Import failed.',
													'navigation-studio'
												)
									);
								}
							} }
						>
							{ __( 'Create navigation', 'navigation-studio' ) }
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
									'Product content appears automatically when WooCommerce exposes it to navigation menus.',
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
									'Diagnostics reports whether WPML or Polylang is active. Language conditions use the current WordPress locale.',
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

function SettingsPage( {
	initial,
	onSaved,
	onError,
}: {
	initial: Settings;
	onSaved: () => void;
	onError: ( message: string ) => void;
} ) {
	const [ settings, setSettings ] = useState( initial );
	const [ saving, setSaving ] = useState( false );
	return (
		<Card>
			<CardBody className="navstudio-settings">
				<ToggleControl
					label={ __(
						'Enable frontend enhancements',
						'navigation-studio'
					) }
					help={ __(
						'Adds responsive visibility, badges, accessible submenu toggles, and mega-menu layout to classic menus.',
						'navigation-studio'
					) }
					checked={ settings.enhancedRendering }
					onChange={ ( enhancedRendering ) =>
						setSettings( { ...settings, enhancedRendering } )
					}
				/>
				<RangeControl
					label={ __(
						'Revisions to retain per navigation',
						'navigation-studio'
					) }
					value={ settings.revisionRetention }
					min={ 10 }
					max={ 500 }
					onChange={ ( revisionRetention ) =>
						setSettings( {
							...settings,
							revisionRetention: revisionRetention ?? 100,
						} )
					}
				/>
				<ToggleControl
					label={ __(
						'Delete plugin data when uninstalled',
						'navigation-studio'
					) }
					help={ __(
						'Removes drafts, revisions, templates, and plugin settings. Published WordPress menus remain intact.',
						'navigation-studio'
					) }
					checked={ settings.deleteDataOnUninstall }
					onChange={ ( deleteDataOnUninstall ) =>
						setSettings( { ...settings, deleteDataOnUninstall } )
					}
				/>
				<Button
					variant="primary"
					isBusy={ saving }
					disabled={ saving }
					onClick={ async () => {
						setSaving( true );
						try {
							setSettings( await api.saveSettings( settings ) );
							onSaved();
						} catch ( reason ) {
							onError(
								reason instanceof Error
									? reason.message
									: __(
											'Settings could not be saved.',
											'navigation-studio'
										)
							);
						} finally {
							setSaving( false );
						}
					} }
				>
					{ __( 'Save settings', 'navigation-studio' ) }
				</Button>
			</CardBody>
		</Card>
	);
}

function TemplateList( { items }: { items: TemplateSummary[] } ) {
	return items.length ? (
		<div className="navstudio-menu-grid">
			{ items.map( ( template ) => (
				<Card key={ template.id }>
					<CardBody>
						<h2>{ template.name }</h2>
						<p className="navstudio-muted">{ template.scope }</p>
					</CardBody>
				</Card>
			) ) }
		</div>
	) : (
		<div className="navstudio-empty">
			<h2>{ __( 'No templates yet', 'navigation-studio' ) }</h2>
			<p>
				{ __(
					'Templates created through the REST API appear here.',
					'navigation-studio'
				) }
			</p>
		</div>
	);
}
