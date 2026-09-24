/* eslint-disable jsx-a11y/no-autofocus -- Opening the modal intentionally moves focus to its command search. */
import { Modal, SearchControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export interface Command {
	name: string;
	shortcut?: string;
	disabled?: boolean;
	run: () => void;
}

export function CommandPalette( {
	commands,
	close,
}: {
	commands: Command[];
	close: () => void;
} ) {
	const [ query, setQuery ] = useState( '' );
	const visible = useMemo(
		() =>
			commands.filter( ( command ) =>
				command.name
					.toLocaleLowerCase()
					.includes( query.toLocaleLowerCase() )
			),
		[ commands, query ]
	);
	return (
		<Modal
			className="navstudio-command-palette"
			title={ __( 'Command palette', 'navigation-studio' ) }
			onRequestClose={ close }
		>
			<SearchControl
				autoFocus
				label={ __( 'Search commands', 'navigation-studio' ) }
				value={ query }
				onChange={ setQuery }
			/>
			<div
				role="listbox"
				aria-label={ __( 'Available commands', 'navigation-studio' ) }
			>
				{ visible.map( ( command ) => (
					<button
						type="button"
						role="option"
						aria-selected="false"
						key={ command.name }
						disabled={ command.disabled }
						onClick={ () => {
							command.run();
							close();
						} }
					>
						<span>{ command.name }</span>
						{ command.shortcut && <kbd>{ command.shortcut }</kbd> }
					</button>
				) ) }
			</div>
		</Modal>
	);
}
