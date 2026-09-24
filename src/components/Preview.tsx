import { ButtonGroup, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { Action } from '../state/editor';
import type { EditorState, NavNode } from '../types';

function PreviewBranch( {
	nodes,
	viewport,
	parent = null,
}: {
	nodes: NavNode[];
	viewport: EditorState[ 'viewport' ];
	parent?: string | null;
} ) {
	const children = nodes.filter( ( node ) => node.parentId === parent );
	if ( ! children.length ) {
		return null;
	}
	return (
		<ul>
			{ children.map( ( node ) => (
				<li
					key={ node.id }
					hidden={ node.responsive[ viewport ] === false }
				>
					<a
						href={ node.url || '#' }
						onClick={ ( event ) => event.preventDefault() }
					>
						{ node.label }
						{ node.appearance.badgeText ? (
							<small>
								{ String( node.appearance.badgeText ) }
							</small>
						) : null }
					</a>
					<PreviewBranch
						nodes={ nodes }
						viewport={ viewport }
						parent={ node.id }
					/>
				</li>
			) ) }
		</ul>
	);
}

export function Preview( {
	state,
	dispatch,
}: {
	state: EditorState;
	dispatch: React.Dispatch< Action >;
} ) {
	return (
		<section
			className="navstudio-preview"
			aria-labelledby="preview-heading"
		>
			<header>
				<h2 id="preview-heading">
					{ __( 'Live preview', 'navigation-studio' ) }
				</h2>
				<ButtonGroup>
					{ ( [ 'desktop', 'tablet', 'mobile' ] as const ).map(
						( viewport ) => (
							<Button
								key={ viewport }
								variant={
									state.viewport === viewport
										? 'primary'
										: 'secondary'
								}
								onClick={ () =>
									dispatch( {
										type: 'SET_VIEWPORT',
										viewport,
									} )
								}
							>
								{ viewport }
							</Button>
						)
					) }
				</ButtonGroup>
			</header>
			<div
				className={ `navstudio-preview__frame is-${ state.viewport }` }
			>
				<nav aria-label={ state.navigation.name }>
					<PreviewBranch
						nodes={ state.navigation.nodes }
						viewport={ state.viewport }
					/>
				</nav>
			</div>
		</section>
	);
}
