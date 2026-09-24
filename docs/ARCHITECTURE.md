# Architecture

## Boundary map

```text
React UI → commands/reducer → versioned REST API → domain validation → native adapter
                                      ↘ draft/revision/template repositories
```

The TypeScript reducer is the optimistic editing boundary. Every tree mutation creates one history entry and passes invariant validation before becoming visible. Server validation repeats the invariants because clients are untrusted.

`NavigationStudio\Domain\Navigation` and `Node` form the normalized transport model. Nodes use UUID-like stable IDs and flat preorder storage with `parentId` relationships. This avoids coupling identity to a database row, array index, or current tree path.

## Native adapters

The classic adapter reads and writes through `wp_get_nav_menu_items()` and `wp_update_nav_menu_item()`. Stable IDs and enhanced metadata use protected menu-item post meta. Published classic menus remain ordinary WordPress menus.

The block adapter reads `wp_navigation` with `parse_blocks()` and saves with `serialize_blocks()`. Identity uses a protected sidecar path map because custom unsupported block attributes would risk block validation failures. Known Navigation Link and Submenu blocks map into the common domain. The sidecar is replaced after save.

Native data is the published source of truth. Custom tables exist only for indexed per-user drafts, immutable revisions, and reusable templates. Each table uses the site `$wpdb->prefix`; multisite network activation creates each site's schema independently.

## Persistence and concurrency

Draft rows are unique by navigation key and user. Each save increments a version and rejects stale expected versions with HTTP 409. Publication also compares the checksum captured when editing began against the current native source. Recovery revisions are created before and after publish. Advisory two-minute locks improve awareness but never replace checksum enforcement.

## Security

All plugin routes use cookie authentication, WordPress REST nonces supplied by Core, and explicit resource capabilities. Nonces are not treated as authorization. Request data passes schema validation and domain sanitization. URLs allow only HTTP(S), mailto, tel, site-relative, or anchor destinations. Imports are JSON only, capped at 5 MB, 5,000 nodes, and JSON depth 100; PHP serialization and uploaded paths are never accepted.

Rendering escapes at the WordPress boundary. No telemetry or external request occurs by default. Uninstall retains all data unless an administrator explicitly enables cleanup.

## Extension points

- `navstudio_loaded`: plugin services are registered.
- `navstudio_navigation_published`: receives new and prior aggregates.
- `navstudio_conditions_visible`: final visibility decision.
- `navstudio_condition_evaluate`: custom condition type evaluation.
- `navstudio_revision_retention`: per-navigation revision limit.

Public providers should return deterministic, sanitized data and must apply their own resource capabilities. Experimental Gutenberg internals are intentionally absent.
