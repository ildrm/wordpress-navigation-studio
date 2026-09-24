export type SourceType = 'classic' | 'block';
export type SaveStatus =
	'saved' | 'dirty' | 'saving' | 'offline' | 'error' | 'conflict';

export interface NavNode {
	id: string;
	parentId: string | null;
	label: string;
	type: string;
	objectType: string;
	objectId: number;
	url: string;
	description: string;
	attributes: {
		target?: string;
		rel?: string;
		title?: string;
		className?: string;
		ariaLabel?: string;
	};
	appearance: Record< string, unknown >;
	responsive: Record< string, unknown >;
	conditions: Array< Record< string, unknown > >;
	dynamic: Record< string, unknown >;
	megaMenu: Record< string, unknown >;
	source: Record< string, unknown >;
}

export interface Navigation {
	key: string;
	name: string;
	sourceType: SourceType;
	sourceId: number;
	nodes: NavNode[];
	settings: Record< string, unknown >;
	checksum: string;
}

export interface MenuSummary {
	key: string;
	id: number;
	name: string;
	sourceType: SourceType;
	itemCount: number;
	modified: string | null;
	hasDraft: boolean;
}

export interface ContentItem {
	id: string;
	objectId: number;
	title: string;
	type: string;
	objectType: string;
	status: string;
	url: string;
	context: string;
}

export interface HistoryEntry {
	nodes: NavNode[];
	label: string;
}

export interface EditorState {
	navigation: Navigation;
	selected: string[];
	expanded: Set< string >;
	past: HistoryEntry[];
	future: HistoryEntry[];
	status: SaveStatus;
	statusMessage: string;
	focusRoot: string | null;
	filter: string;
	viewport: 'desktop' | 'tablet' | 'mobile';
	draftVersion: number | null;
	publishedChecksum: string;
}

declare global {
	interface Window {
		navStudioSettings: {
			apiRoot: string;
			nonce: string;
			adminUrl: string;
			siteUrl: string;
			locale: string;
			isRtl: boolean;
			canPublish: boolean;
		};
	}
}
