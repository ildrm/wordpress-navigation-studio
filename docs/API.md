# REST API

Namespace: `navigation-studio/v1`. Requests use WordPress cookie authentication and `X-WP-Nonce`. Read/edit operations require `edit_theme_options` or `manage_navigation_studio`; publish requires `publish_navigation_studio`; settings/import/template administration requires `manage_options` or `manage_navigation_templates`.

| Method | Route | Purpose |
| --- | --- | --- |
| GET/POST | `/menus` | List or create native navigations |
| GET | `/menus/{type:id}` | Read normalized navigation |
| GET/PUT/DELETE | `/menus/{type:id}/draft` | Read, version-save, or discard user draft |
| POST | `/menus/{type:id}/publish` | Conflict-check and publish |
| GET | `/menus/{type:id}/revisions` | List recovery snapshots |
| POST | `/menus/{type:id}/revisions/{id}/restore` | Restore after creating a recovery point |
| GET | `/menus/{type:id}/health` | Structural/accessibility diagnostics |
| GET | `/menus/{type:id}/export` | Versioned portable JSON |
| POST/DELETE | `/menus/{type:id}/lock` | Refresh/release advisory lock |
| GET | `/content` | Paginated content/taxonomy search |
| POST | `/import/preview`, `/import/commit` | Validate then create imported navigation |
| GET/POST/DELETE | `/templates` | Reusable copy templates |
| GET/PUT | `/settings` | Global opt-in behavior |
| GET | `/diagnostics` | Non-secret environment details |

A publish request includes `publishedChecksum`. HTTP 409 means the native source or draft version changed; clients must retain their local state. Error bodies use normal `WP_Error` REST serialization.
