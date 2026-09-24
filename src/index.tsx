import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/admin.scss';

const root = document.getElementById( 'navstudio-root' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
