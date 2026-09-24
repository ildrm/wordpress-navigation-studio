import {
	Button,
	CheckboxControl,
	Panel,
	PanelBody,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import type { Action } from '../state/editor';
import type { EditorState, NavNode } from '../types';

export function Inspector( {
	state,
	dispatch,
}: {
	state: EditorState;
	dispatch: React.Dispatch< Action >;
} ) {
	const selected = state.navigation.nodes.filter( ( node ) =>
		state.selected.includes( node.id )
	);
	if ( selected.length === 0 ) {
		return (
			<aside className="navstudio-panel navstudio-inspector">
				<header>
					<h2>{ __( 'Properties', 'navigation-studio' ) }</h2>
				</header>
				<div className="navstudio-empty">
					<p>
						{ __(
							'Select an item to edit its properties.',
							'navigation-studio'
						) }
					</p>
				</div>
			</aside>
		);
	}
	if ( selected.length > 1 ) {
		return <BulkInspector nodes={ selected } dispatch={ dispatch } />;
	}
	const node = selected[ 0 ];
	if ( node.type === 'block-unsupported' ) {
		return (
			<aside className="navstudio-panel navstudio-inspector">
				<header>
					<h2>{ __( 'Preserved block', 'navigation-studio' ) }</h2>
				</header>
				<div className="navstudio-empty">
					<p>
						{ __(
							'Navigation Studio does not edit this block type. Its complete block data will be preserved when you publish.',
							'navigation-studio'
						) }
					</p>
					<code>{ node.objectType }</code>
				</div>
			</aside>
		);
	}
	const update = ( patch: Partial< NavNode >, label: string ) =>
		dispatch( { type: 'UPDATE_NODE', id: node.id, patch, label } );
	const attrs = node.attributes;
	const loggedInCondition = node.conditions.find(
		( condition ) => condition.type === 'logged_in'
	);
	let audience: 'everyone' | 'logged-in' | 'logged-out' = 'everyone';
	if ( loggedInCondition ) {
		audience = loggedInCondition.value ? 'logged-in' : 'logged-out';
	}
	return (
		<aside
			className="navstudio-panel navstudio-inspector"
			aria-labelledby="inspector-heading"
		>
			<header>
				<h2 id="inspector-heading">
					{ __( 'Item properties', 'navigation-studio' ) }
				</h2>
				<span className="navstudio-badge">{ node.objectType }</span>
			</header>
			<Panel>
				<PanelBody
					title={ __( 'General', 'navigation-studio' ) }
					initialOpen
				>
					<TextControl
						label={ __( 'Navigation label', 'navigation-studio' ) }
						value={ node.label }
						onChange={ ( label ) =>
							update(
								{ label },
								__( 'Changed label', 'navigation-studio' )
							)
						}
					/>
					<TextControl
						label={ __( 'Destination URL', 'navigation-studio' ) }
						value={ node.url }
						onChange={ ( url ) =>
							update(
								{
									url,
									...( node.objectId
										? {
												type: 'custom',
												objectType: 'custom',
												objectId: 0,
											}
										: {} ),
								},
								__( 'Changed destination', 'navigation-studio' )
							)
						}
						help={
							node.objectId
								? __(
										'This item is linked to WordPress content. A custom URL disables URL synchronization.',
										'navigation-studio'
									)
								: undefined
						}
					/>
					<TextareaControl
						label={ __( 'Description', 'navigation-studio' ) }
						value={ node.description }
						onChange={ ( description ) =>
							update(
								{ description },
								__( 'Changed description', 'navigation-studio' )
							)
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Link', 'navigation-studio' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Open in a new tab', 'navigation-studio' ) }
						checked={ attrs.target === '_blank' }
						onChange={ ( checked ) =>
							update(
								{
									attributes: {
										...attrs,
										target: checked ? '_blank' : '',
									},
								},
								__( 'Changed link target', 'navigation-studio' )
							)
						}
					/>
					<TextControl
						label={ __(
							'Relationship (rel)',
							'navigation-studio'
						) }
						value={ attrs.rel ?? '' }
						onChange={ ( rel ) =>
							update(
								{ attributes: { ...attrs, rel } },
								__(
									'Changed relationship',
									'navigation-studio'
								)
							)
						}
					/>
					<TextControl
						label={ __( 'Accessible label', 'navigation-studio' ) }
						value={ attrs.ariaLabel ?? '' }
						onChange={ ( ariaLabel ) =>
							update(
								{ attributes: { ...attrs, ariaLabel } },
								__(
									'Changed accessible label',
									'navigation-studio'
								)
							)
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Appearance', 'navigation-studio' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'CSS classes', 'navigation-studio' ) }
						value={ attrs.className ?? '' }
						onChange={ ( className ) =>
							update(
								{ attributes: { ...attrs, className } },
								__( 'Changed CSS classes', 'navigation-studio' )
							)
						}
					/>
					<TextControl
						label={ __( 'Badge text', 'navigation-studio' ) }
						value={ String( node.appearance.badgeText ?? '' ) }
						onChange={ ( badgeText ) =>
							update(
								{
									appearance: {
										...node.appearance,
										badgeText,
									},
								},
								__( 'Changed badge', 'navigation-studio' )
							)
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Responsive', 'navigation-studio' ) }
					initialOpen={ false }
				>
					{ [ 'desktop', 'tablet', 'mobile' ].map( ( viewport ) => (
						<CheckboxControl
							key={ viewport }
							label={ sprintf(
								/* translators: %s is a viewport name such as desktop. */
								__( 'Show on %s', 'navigation-studio' ),
								viewport
							) }
							checked={ node.responsive[ viewport ] !== false }
							onChange={ ( checked ) =>
								update(
									{
										responsive: {
											...node.responsive,
											[ viewport ]: checked,
										},
									},
									__(
										'Changed responsive visibility',
										'navigation-studio'
									)
								)
							}
						/>
					) ) }
					<SelectControl
						label={ __(
							'Mobile submenu behavior',
							'navigation-studio'
						) }
						value={
							String( node.responsive.submenu ?? 'accordion' ) as
								'accordion' | 'drilldown' | 'expanded'
						}
						onChange={ ( submenu ) =>
							update(
								{ responsive: { ...node.responsive, submenu } },
								__(
									'Changed mobile behavior',
									'navigation-studio'
								)
							)
						}
						options={ [
							{
								label: __( 'Accordion', 'navigation-studio' ),
								value: 'accordion',
							},
							{
								label: __( 'Drill-down', 'navigation-studio' ),
								value: 'drilldown',
							},
							{
								label: __(
									'Always expanded',
									'navigation-studio'
								),
								value: 'expanded',
							},
						] }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Visibility', 'navigation-studio' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Audience', 'navigation-studio' ) }
						value={ audience }
						onChange={ ( value ) =>
							update(
								{
									conditions:
										value === 'everyone'
											? []
											: [
													{
														type: 'logged_in',
														operator: 'is',
														value:
															value ===
															'logged-in',
													},
												],
								},
								__( 'Changed visibility', 'navigation-studio' )
							)
						}
						options={ [
							{
								label: __( 'Everyone', 'navigation-studio' ),
								value: 'everyone',
							},
							{
								label: __(
									'Logged-in visitors',
									'navigation-studio'
								),
								value: 'logged-in',
							},
							{
								label: __(
									'Logged-out visitors',
									'navigation-studio'
								),
								value: 'logged-out',
							},
						] }
					/>
					<p className="navstudio-help">
						{ __(
							'Additional condition providers can be registered through the PHP API.',
							'navigation-studio'
						) }
					</p>
				</PanelBody>
				<PanelBody
					title={ __( 'Mega menu', 'navigation-studio' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Enable mega menu for this branch',
							'navigation-studio'
						) }
						checked={ Boolean( node.megaMenu.enabled ) }
						onChange={ ( enabled ) =>
							update(
								{
									megaMenu: {
										...node.megaMenu,
										enabled,
										columns: node.megaMenu.columns ?? 3,
									},
								},
								__( 'Changed mega menu', 'navigation-studio' )
							)
						}
					/>
					{ Boolean( node.megaMenu.enabled ) && (
						<SelectControl
							label={ __( 'Columns', 'navigation-studio' ) }
							value={ String( node.megaMenu.columns ?? 3 ) }
							onChange={ ( columns ) =>
								update(
									{
										megaMenu: {
											...node.megaMenu,
											columns: Number( columns ),
										},
									},
									__(
										'Changed mega menu columns',
										'navigation-studio'
									)
								)
							}
							options={ [ 2, 3, 4, 5, 6 ].map( ( value ) => ( {
								label: String( value ),
								value: String( value ),
							} ) ) }
						/>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Developer', 'navigation-studio' ) }
					initialOpen={ false }
				>
					<dl className="navstudio-definition">
						<dt>{ __( 'Stable ID', 'navigation-studio' ) }</dt>
						<dd>
							<code>{ node.id }</code>
						</dd>
						<dt>
							{ __( 'Native object ID', 'navigation-studio' ) }
						</dt>
						<dd>{ node.objectId || '—' }</dd>
						<dt>{ __( 'Type', 'navigation-studio' ) }</dt>
						<dd>
							{ node.type } / { node.objectType }
						</dd>
					</dl>
				</PanelBody>
			</Panel>
		</aside>
	);
}

function BulkInspector( {
	nodes,
	dispatch,
}: {
	nodes: NavNode[];
	dispatch: React.Dispatch< Action >;
} ) {
	return (
		<aside className="navstudio-panel navstudio-inspector">
			<header>
				<h2>
					{ sprintf(
						/* translators: %d is the number of selected navigation items. */
						__( '%d items selected', 'navigation-studio' ),
						nodes.length
					) }
				</h2>
			</header>
			<Panel>
				<PanelBody
					title={ __( 'Bulk appearance', 'navigation-studio' ) }
					initialOpen
				>
					<TextControl
						label={ __( 'CSS classes', 'navigation-studio' ) }
						value=""
						placeholder={ __(
							'Set the same classes on all selected items',
							'navigation-studio'
						) }
						onChange={ () => undefined }
						onBlur={ ( event ) => {
							if ( event.currentTarget.value ) {
								dispatch( {
									type: 'BULK_UPDATE',
									ids: nodes.map( ( node ) => node.id ),
									patch: {
										attributes: {
											className:
												event.currentTarget.value,
										},
									},
									label: __(
										'Bulk changed CSS classes',
										'navigation-studio'
									),
								} );
							}
						} }
					/>
					<Button
						isDestructive
						onClick={ () =>
							dispatch( {
								type: 'DELETE',
								ids: nodes.map( ( node ) => node.id ),
							} )
						}
					>
						{ __( 'Delete selected items', 'navigation-studio' ) }
					</Button>
				</PanelBody>
			</Panel>
		</aside>
	);
}
