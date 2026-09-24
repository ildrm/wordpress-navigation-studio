/* eslint-disable no-nested-ternary -- Loading, empty, and result states are a single rendering decision. */
import {
	Button,
	CheckboxControl,
	SearchControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { createNode } from '../domain/tree';
import type { ContentItem, NavNode } from '../types';

export function ContentLibrary( {
	onAdd,
}: {
	onAdd: ( nodes: NavNode[] ) => void;
} ) {
	const [ search, setSearch ] = useState( '' );
	const [ kind, setKind ] = useState< 'all' | 'post' | 'taxonomy' >( 'all' );
	const [ items, setItems ] = useState< ContentItem[] >( [] );
	const [ chosen, setChosen ] = useState< Set< string > >( new Set() );
	const [ loading, setLoading ] = useState( true );
	useEffect( () => {
		const controller = new AbortController();
		const timer = setTimeout( () => {
			setLoading( true );
			api.search( search, kind, controller.signal )
				.then( ( result ) => setItems( result.items ) )
				.catch( ( error: Error ) => {
					if ( error.name !== 'AbortError' ) {
						setItems( [] );
					}
				} )
				.finally( () => {
					if ( ! controller.signal.aborted ) {
						setLoading( false );
					}
				} );
		}, 250 );
		return () => {
			clearTimeout( timer );
			controller.abort();
		};
	}, [ search, kind ] );
	const add = () => {
		onAdd(
			items
				.filter( ( item ) => chosen.has( item.id ) )
				.map( ( item ) =>
					createNode( {
						label: item.title,
						url: item.url,
						type: item.type,
						objectType: item.objectType,
						objectId: item.objectId,
					} )
				)
		);
		setChosen( new Set() );
	};
	return (
		<section
			className="navstudio-panel navstudio-library"
			aria-labelledby="library-heading"
		>
			<header>
				<h2 id="library-heading">
					{ __( 'Content', 'navigation-studio' ) }
				</h2>
			</header>
			<div className="navstudio-panel__controls">
				<SearchControl
					label={ __( 'Search site content', 'navigation-studio' ) }
					value={ search }
					onChange={ setSearch }
				/>
				<SelectControl
					label={ __( 'Content type', 'navigation-studio' ) }
					value={ kind }
					onChange={ ( value ) =>
						setKind( value as 'all' | 'post' | 'taxonomy' )
					}
					options={ [
						{
							label: __( 'Everything', 'navigation-studio' ),
							value: 'all',
						},
						{
							label: __( 'Posts and pages', 'navigation-studio' ),
							value: 'post',
						},
						{
							label: __( 'Taxonomies', 'navigation-studio' ),
							value: 'taxonomy',
						},
					] }
				/>
			</div>
			<div className="navstudio-library__results" aria-busy={ loading }>
				{ loading ? (
					<div className="navstudio-centered">
						<Spinner />
					</div>
				) : items.length ? (
					items.map( ( item ) => (
						<div key={ item.id } className="navstudio-library-item">
							<CheckboxControl
								checked={ chosen.has( item.id ) }
								onChange={ ( checked ) => {
									const next = new Set( chosen );
									if ( checked ) {
										next.add( item.id );
									} else {
										next.delete( item.id );
									}
									setChosen( next );
								} }
							/>
							<span>
								<strong>{ item.title }</strong>
								<small>
									{ item.context } · { item.status }
								</small>
							</span>
						</div>
					) )
				) : (
					<p className="navstudio-muted">
						{ __( 'No matching content.', 'navigation-studio' ) }
					</p>
				) }
			</div>
			<footer>
				<Button
					variant="primary"
					disabled={ ! chosen.size }
					onClick={ add }
				>
					{ chosen.size
						? __( 'Add selected', 'navigation-studio' )
						: __( 'Select items to add', 'navigation-studio' ) }
				</Button>
				<Button
					variant="tertiary"
					onClick={ () =>
						onAdd( [
							createNode( {
								label: __( 'Custom link', 'navigation-studio' ),
							} ),
						] )
					}
				>
					{ __( 'Add custom link', 'navigation-studio' ) }
				</Button>
			</footer>
		</section>
	);
}
