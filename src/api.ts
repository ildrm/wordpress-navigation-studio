import apiFetch from '@wordpress/api-fetch';
import type {
	ContentItem,
	MenuSummary,
	Navigation,
	Settings,
	TemplateSummary,
} from './types';

apiFetch.use(
	apiFetch.createNonceMiddleware( window.navStudioSettings.nonce )
);

const path = ( value: string ) => `/navigation-studio/v1/${ value }`;

export const api = {
	menus: () =>
		apiFetch< { items: MenuSummary[] } >( { path: path( 'menus' ) } ),
	menu: ( key: string ) =>
		apiFetch< Navigation >( {
			path: path( `menus/${ encodeURIComponent( key ) }` ),
		} ),
	createMenu: ( name: string, sourceType: 'classic' | 'block' ) =>
		apiFetch< Navigation >( {
			path: path( 'menus' ),
			method: 'POST',
			data: { name, sourceType },
		} ),
	draft: ( key: string ) =>
		apiFetch< { version: number; navigation: Navigation } | null >( {
			path: path( `menus/${ encodeURIComponent( key ) }/draft` ),
		} ),
	saveDraft: ( navigation: Navigation, version: number | null ) =>
		apiFetch< { version: number; navigation: Navigation } >( {
			path: path(
				`menus/${ encodeURIComponent( navigation.key ) }/draft`
			),
			method: 'PUT',
			data: { navigation, version },
		} ),
	publish: ( navigation: Navigation, publishedChecksum: string ) =>
		apiFetch< Navigation >( {
			path: path(
				`menus/${ encodeURIComponent( navigation.key ) }/publish`
			),
			method: 'POST',
			data: { navigation, publishedChecksum },
		} ),
	search: ( search: string, kind = 'all', signal?: AbortSignal ) =>
		apiFetch< { items: ContentItem[]; total: number } >( {
			path: path(
				`content?search=${ encodeURIComponent( search ) }&kind=${ kind }&per_page=30`
			),
			signal,
		} ),
	health: ( key: string ) =>
		apiFetch< {
			issues: Array< {
				severity: string;
				message: string;
				node_id: string;
			} >;
		} >( { path: path( `menus/${ encodeURIComponent( key ) }/health` ) } ),
	exportNavigation: ( key: string ) =>
		apiFetch< Record< string, unknown > >( {
			path: path( `menus/${ encodeURIComponent( key ) }/export` ),
		} ),
	lock: ( key: string ) =>
		apiFetch< { owned: boolean; userName?: string } >( {
			path: path( `menus/${ encodeURIComponent( key ) }/lock` ),
			method: 'POST',
		} ),
	unlock: ( key: string ) =>
		apiFetch( {
			path: path( `menus/${ encodeURIComponent( key ) }/lock` ),
			method: 'DELETE',
		} ),
	settings: () => apiFetch< Settings >( { path: path( 'settings' ) } ),
	saveSettings: ( settings: Settings ) =>
		apiFetch< Settings >( {
			path: path( 'settings' ),
			method: 'PUT',
			data: settings,
		} ),
	diagnostics: () =>
		apiFetch< Record< string, unknown > >( {
			path: path( 'diagnostics' ),
		} ),
	importPreview: ( content: string ) =>
		apiFetch< {
			summary: { name: string; itemCount: number; sourceType: string };
		} >( {
			path: path( 'import/preview' ),
			method: 'POST',
			data: { content },
		} ),
	importCommit: ( content: string, sourceType: 'classic' | 'block' ) =>
		apiFetch< Navigation >( {
			path: path( 'import/commit' ),
			method: 'POST',
			data: { content, sourceType },
		} ),
	templates: () =>
		apiFetch< { items: TemplateSummary[] } >( {
			path: path( 'templates' ),
		} ),
};
