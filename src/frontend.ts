import './styles/frontend.scss';

let submenuIndex = 0;

function enhanceNavigation( nav: HTMLElement ) {
	const parents = nav.querySelectorAll< HTMLLIElement >( 'li:has(> ul)' );
	parents.forEach( ( item, index ) => {
		const submenu = item.querySelector< HTMLElement >( ':scope > ul' );
		if ( ! submenu ) {
			return;
		}
		submenu.id ||= `navstudio-submenu-${ ++submenuIndex }-${ index }`;
		const toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.className = 'navstudio-submenu-toggle';
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.setAttribute( 'aria-controls', submenu.id );
		const icon = document.createElement( 'span' );
		icon.setAttribute( 'aria-hidden', 'true' );
		icon.textContent = '▾';
		const label = document.createElement( 'span' );
		label.className = 'screen-reader-text';
		label.textContent = 'Toggle submenu';
		toggle.append( icon, label );
		item.querySelector( ':scope > a' )?.after( toggle );
		const close = () => {
			toggle.setAttribute( 'aria-expanded', 'false' );
			item.classList.remove( 'is-open' );
		};
		toggle.addEventListener( 'click', () => {
			const open = toggle.getAttribute( 'aria-expanded' ) !== 'true';
			toggle.setAttribute( 'aria-expanded', String( open ) );
			item.classList.toggle( 'is-open', open );
		} );
		item.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				close();
				( toggle as HTMLElement ).focus();
			}
		} );
	} );
}

document
	.querySelectorAll< HTMLElement >( '.menu, .wp-block-navigation' )
	.forEach( enhanceNavigation );
