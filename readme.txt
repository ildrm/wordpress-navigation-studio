=== Navigation Studio ===
Contributors: navigation-studio
Tags: navigation, menus, block themes, accessibility, mega menu
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage classic menus and block navigation through a fast, visual, accessible workspace.

== Description ==

Navigation Studio brings content discovery, hierarchy editing, item properties, responsive preview, drafts, publishing, revisions, templates, and portable migration into one dedicated WordPress workspace.

Highlights:

* One editor for classic menus and `wp_navigation`.
* Accessible tree editing with keyboard and explicit move controls.
* Multi-selection, bulk editing, inline rename, focus mode, undo, and redo.
* Per-user drafts, optimistic conflict protection, recovery revisions, and browser-local failure recovery.
* Search across public post types and taxonomies.
* Responsive item visibility, conditions, badges, and mega-menu metadata.
* Versioned JSON import/export and reusable templates.
* No telemetry. Native WordPress content remains the published source of truth.

== Installation ==

1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin.
2. Activate Navigation Studio.
3. Open Navigation Studio in the WordPress admin menu.
4. Choose an existing navigation or create one.

Activation never rewrites an existing menu. Deactivation leaves native menus intact. Plugin-only data is retained unless the administrator explicitly enables removal on uninstall.

== Frequently Asked Questions ==

= Does this replace my theme navigation automatically? =

No. Published edits update WordPress native classic menus or `wp_navigation`. Themes continue to render those sources normally. Progressive frontend enhancements are scoped and loaded only when applicable.

= Is drag and drop required? =

No. Every structural operation has keyboard commands and an explicit Move dialog.

= Does the plugin collect analytics? =

No. There is no telemetry or mandatory external service.

= Can I recover an earlier version? =

Yes. Navigation Studio creates recovery snapshots before publication and restoration.

== Screenshots ==

1. Navigation dashboard with classic and block sources.
2. Three-panel editor with content library, navigation tree, and inspector.
3. Responsive preview and accessible Move dialog.
4. Diagnostics and portable import preview.

== Changelog ==

= 1.0.0 =

* Initial production release.

== Upgrade Notice ==

= 1.0.0 =

Initial release. Back up WordPress before installing any new production plugin.
