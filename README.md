# Navigation Studio

Navigation Studio is a visual navigation workspace for WordPress. It provides one editing experience for classic menus and `wp_navigation` entities while keeping WordPress native data as the published source of truth.

The plugin is designed for administrators and development teams that need structured tree editing, private drafts, conflict-aware publishing, recovery revisions, and portable navigation data without replacing the APIs their themes already use.

## Highlights

- Edit classic menus and block navigations through one normalized tree model.
- Search posts, pages, custom post types, and taxonomy terms from the editor.
- Reorder, nest, duplicate, rename, multi-select, and bulk-edit navigation items.
- Use keyboard commands or the explicit Move dialog instead of drag and drop.
- Undo and redo up to 100 editor operations.
- Save per-user drafts with atomic optimistic version checks.
- Detect published-source conflicts before overwriting native navigation data.
- Create recovery revisions before and after publication.
- Import and export versioned JSON with bounded validation.
- Preserve registered but unsupported blocks during block-navigation round trips.
- Opt into classic-menu badges, responsive visibility, accessible submenu toggles, and mega-menu columns.
- Keep failed saves in browser session storage for recovery.
- Run without telemetry or mandatory third-party services.

## Requirements

### WordPress runtime

- WordPress 7.0 or newer
- PHP 7.4 or newer
- A user with `edit_theme_options` or the appropriate Navigation Studio capability

### Development

- Node.js 24.18.0, pinned in [`.nvmrc`](.nvmrc)
- npm
- Composer 2
- Docker Desktop or another Docker-compatible runtime for `wp-env`

## Installation

### Install a release ZIP

1. Download a release archive.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate **Navigation Studio**.
4. Open **Navigation Studio** from the WordPress administration menu.

Activation creates plugin-owned draft, revision, and template tables. It does not rewrite existing WordPress menus. Deactivation leaves native navigation and plugin data intact.

### Install from source

```bash
git clone https://github.com/ildrm/wordpress-navigation-studio.git
cd wordpress-navigation-studio
nvm use
npm ci
composer install
npm run build
```

Place or symlink the repository in `wp-content/plugins`, then activate `navigation-studio.php`. The compiled `build/` directory is required by the admin application and optional frontend enhancements; Composer dependencies are development-only and are not included in the runtime package.

## Quick start

1. Open **Navigation Studio** in WordPress administration.
2. Select an existing classic or block navigation, or choose **Create navigation**.
3. Add WordPress content from the left panel or create a custom link.
4. Organize items in the tree and edit the selected item in the inspector.
5. Save a private draft or publish the changes to the native WordPress source.

Drafts are scoped to the current user. Publishing compares the checksum captured when the editor opened with the current native source; a mismatch returns a conflict and keeps local work intact.

See the [user guide](docs/USER-GUIDE.md) for editor controls and keyboard shortcuts.

## Frontend enhancements

Frontend behavior is disabled by default. Enable it under **Navigation Studio → Settings** to add the following enhancements to classic menus carrying Navigation Studio metadata:

- per-viewport item visibility;
- badge labels;
- accessible submenu toggle buttons;
- configurable mega-menu columns; and
- conditional visibility for supported conditions.

Published menus remain standard WordPress menus. Disabling the option stops loading the enhancement assets. Block navigations continue to render through WordPress's native Navigation block pipeline.

## Development workflow

Install dependencies and build the assets:

```bash
nvm use
npm ci
composer install
npm run build
```

Start the local WordPress environment:

```bash
npm run env:start
```

The default `wp-env` site is available at `http://localhost:8888`. WordPress's standard `wp-env` credentials are `admin` / `password`.

### Available commands

| Command | Purpose |
| --- | --- |
| `npm run start` | Watch and rebuild admin and frontend assets. |
| `npm run build` | Create production assets in `build/`. |
| `npm run format` | Format JavaScript, TypeScript, JSON, and related source files. |
| `npm run lint:js` | Run the WordPress ESLint configuration. |
| `npm run lint:css` | Lint SCSS with the WordPress Stylelint configuration. |
| `npm run lint:types` | Type-check the TypeScript project without emitting files. |
| `npm run test:unit` | Run the JavaScript unit tests. |
| `npm run test:e2e` | Run Playwright tests against the development site. |
| `npm run env:start` | Start the development `wp-env` site. |
| `npm run env:test:start` | Start the isolated WordPress/PHP test environment. |
| `npm run env:stop` | Stop the default `wp-env` environment. |
| `composer lint` | Run WordPress Coding Standards and PHP compatibility checks. |
| `composer phpstan` | Run PHPStan at level 6 with WordPress extensions. |
| `composer test` | Run PHPUnit when a WordPress test library is available. |

## Testing

Run the local static and unit-test suite:

```bash
npm run format
npm run lint:js
npm run lint:css
npm run lint:types
npm run test:unit
npm run build
composer validate --strict
composer lint
composer phpstan
```

PHPUnit uses the WordPress test library. Start the dedicated environment, then run the suite inside its test container:

```bash
npm run env:test:start
npx wp-env run tests-cli --config .wp-env.tests.json \
  --env-cwd=wp-content/plugins/wordpress-navigation-studio composer test
```

For browser tests, start the development environment in one terminal and run Playwright in another:

```bash
npm run env:start
npm run test:e2e
```

More detail is available in [docs/TESTING.md](docs/TESTING.md).

## Architecture

```text
React editor
    ↓
commands and reducer
    ↓
versioned WordPress REST API
    ↓
domain validation
    ↓
classic-menu or block-navigation adapter

drafts / revisions / templates → plugin-owned tables
published navigation           → native WordPress storage
```

The client uses a flat preorder node collection with stable IDs and `parentId` relationships. The PHP domain validates identity, ordering, parent references, cycles, depth, node count, and URL protocols again at the trust boundary.

The classic adapter publishes through WordPress menu APIs. The block adapter uses `parse_blocks()` and `serialize_blocks()`, stores stable IDs in a sidecar map, and preserves unsupported registered blocks as validated serialized block data.

Read the [architecture guide](docs/ARCHITECTURE.md) for persistence and extension details.

## REST API

The API namespace is `navigation-studio/v1`. It supports:

- native navigation listing, creation, reading, and publishing;
- per-user draft save, read, and discard operations;
- revision listing and restoration;
- advisory edit locks;
- paginated content discovery;
- navigation health checks;
- JSON import and export;
- template storage; and
- settings and diagnostics.

Requests use WordPress cookie authentication, REST nonces, and explicit capability callbacks. See the [REST API reference](docs/API.md) for routes and authorization requirements.

## Security and privacy

- Every REST route has a capability check.
- The server treats client tree data as untrusted and revalidates it before persistence.
- URLs are restricted to HTTP, HTTPS, `mailto`, `tel`, site-relative paths, and anchors.
- Import payloads are limited to 5 MB, a JSON depth of 100, and 5,000 nodes.
- Draft and publish operations use version/checksum conflict protection.
- No telemetry is collected and no external requests are required.
- Plugin data is retained on uninstall unless an administrator explicitly enables deletion.

Security reports should avoid public disclosure until a fix is available. Use the repository owner's private contact channel or GitHub's private vulnerability reporting when enabled.

## Project structure

```text
includes/
  Adapters/         Native classic-menu and block-navigation adapters
  Admin/            WordPress admin bootstrap and assets
  Application/      Search, conditions, health, and transfer services
  Domain/           Navigation model and tree invariants
  Frontend/         Opt-in classic-menu enhancements
  Infrastructure/   Schema, repositories, revisions, locks, and templates
  REST/             Versioned API routes and permissions
src/
  components/       React admin UI
  domain/           Client-side tree operations
  state/            Editor reducer and undo/redo history
  styles/           Admin and frontend SCSS
tests/               JavaScript and WordPress/PHP tests
specs/               Playwright scenarios
tools/               Runtime smoke and release tooling
docs/                User, API, architecture, and testing documentation
```

## Building a release

```bash
npm ci
composer install
npm run build
php tools/build-release.php
```

The release builder uses a strict allowlist and writes `dist/navigation-studio-1.0.0.zip`. Development dependencies, tests, source assets, and local configuration are excluded from the archive.

## Documentation

- [User guide](docs/USER-GUIDE.md)
- [Architecture](docs/ARCHITECTURE.md)
- [REST API](docs/API.md)
- [Testing and release](docs/TESTING.md)
- [Requirement traceability](docs/REQUIREMENTS.md)

## License

Navigation Studio is licensed under the [GNU General Public License v2.0 or later](LICENSE).
