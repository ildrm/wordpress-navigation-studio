/* eslint-disable no-nested-ternary -- Dashboard loading, empty, and populated states are mutually exclusive. */
import {
	Button,
	Card,
	CardBody,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { api } from '../api';
import type { MenuSummary } from '../types';

interface Props {
	onEdit: ( key: string ) => void;
}

export function Dashboard( { onEdit }: Props ) {
	const [ menus, setMenus ] = useState< MenuSummary[] | null >( null );
	const [ search, setSearch ] = useState( '' );
	const [ sort, setSort ] = useState< 'name' | 'count' >( 'name' );
	const [ error, setError ] = useState( '' );
	const [ creating, setCreating ] = useState( false );
	const [ name, setName ] = useState( '' );
	const [ sourceType, setSourceType ] = useState< 'classic' | 'block' >(
		'classic'
	);

	const load = () =>
		api
			.menus()
			.then( ( result ) => setMenus( result.items ) )
			.catch( ( reason ) => setError( reason.message ) );
	useEffect( () => {
		void load();
	}, [] );
	const visible = useMemo(
		() =>
			( menus ?? [] )
				.filter( ( menu ) =>
					menu.name
						.toLocaleLowerCase()
						.includes( search.toLocaleLowerCase() )
				)
				.sort( ( a, b ) =>
					sort === 'count'
						? b.itemCount - a.itemCount
						: a.name.localeCompare( b.name )
				),
		[ menus, search, sort ]
	);
	const create = async () => {
		if ( ! name.trim() ) {
			return;
		}
		try {
			const menu = await api.createMenu( name.trim(), sourceType );
			onEdit( menu.key );
		} catch ( reason ) {
			setError(
				reason instanceof Error
					? reason.message
					: __( 'Menu creation failed.', 'navigation-studio' )
			);
		}
	};

	return (
		<main className="navstudio-dashboard" aria-labelledby="navstudio-title">
			<header className="navstudio-page-header">
				<div>
					<h1 id="navstudio-title">
						{ __( 'Navigation Studio', 'navigation-studio' ) }
					</h1>
					<p>
						{ __(
							'Create, organize, preview, and publish every site navigation.',
							'navigation-studio'
						) }
					</p>
				</div>
				<Button
					variant="primary"
					onClick={ () => setCreating( ! creating ) }
				>
					{ __( 'Create navigation', 'navigation-studio' ) }
				</Button>
			</header>
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ creating && (
				<Card className="navstudio-create">
					<CardBody>
						<h2>{ __( 'New navigation', 'navigation-studio' ) }</h2>
						<div className="navstudio-inline-form">
							<TextControl
								label={ __( 'Name', 'navigation-studio' ) }
								value={ name }
								onChange={ setName }
							/>
							<SelectControl
								label={ __( 'Type', 'navigation-studio' ) }
								value={ sourceType }
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
								onChange={ ( value ) =>
									setSourceType(
										value as 'classic' | 'block'
									)
								}
							/>
							<Button variant="primary" onClick={ create }>
								{ __( 'Create and edit', 'navigation-studio' ) }
							</Button>
						</div>
					</CardBody>
				</Card>
			) }
			<div className="navstudio-dashboard-tools">
				<TextControl
					label={ __( 'Search navigations', 'navigation-studio' ) }
					hideLabelFromVision
					value={ search }
					onChange={ setSearch }
					placeholder={ __(
						'Search navigations…',
						'navigation-studio'
					) }
				/>
				<SelectControl
					label={ __( 'Sort', 'navigation-studio' ) }
					hideLabelFromVision
					value={ sort }
					onChange={ ( value ) =>
						setSort( value as 'name' | 'count' )
					}
					options={ [
						{
							label: __( 'Name', 'navigation-studio' ),
							value: 'name',
						},
						{
							label: __( 'Item count', 'navigation-studio' ),
							value: 'count',
						},
					] }
				/>
			</div>
			{ ! menus ? (
				<div className="navstudio-centered">
					<Spinner />
					<span>
						{ __( 'Loading navigations…', 'navigation-studio' ) }
					</span>
				</div>
			) : visible.length === 0 ? (
				<div className="navstudio-empty">
					<h2>
						{ __( 'No navigations found', 'navigation-studio' ) }
					</h2>
					<p>
						{ search
							? __(
									'Try a different search.',
									'navigation-studio'
								)
							: __(
									'Create your first navigation to begin.',
									'navigation-studio'
								) }
					</p>
				</div>
			) : (
				<div className="navstudio-menu-grid">
					{ visible.map( ( menu ) => (
						<Card key={ menu.key } className="navstudio-menu-card">
							<CardBody>
								<div className="navstudio-card-type">
									{ menu.sourceType === 'classic'
										? __( 'Classic', 'navigation-studio' )
										: __( 'Block', 'navigation-studio' ) }
								</div>
								<h2>{ menu.name }</h2>
								<p>
									{ sprintf(
										/* translators: %d is the number of navigation items. */
										_n(
											'%d item',
											'%d items',
											menu.itemCount,
											'navigation-studio'
										),
										menu.itemCount
									) }
								</p>
								<div className="navstudio-badges">
									{ menu.hasDraft && (
										<span>
											{ __(
												'Draft changes',
												'navigation-studio'
											) }
										</span>
									) }
								</div>
								<Button
									variant="secondary"
									onClick={ () => onEdit( menu.key ) }
								>
									{ __( 'Edit', 'navigation-studio' ) }
								</Button>
							</CardBody>
						</Card>
					) ) }
				</div>
			) }
		</main>
	);
}
