# Navigation Studio

Navigation Studio is a visual, accessible navigation management system for WordPress. It presents native classic menus and block-theme `wp_navigation` entities through one domain model while preserving WordPress as the published source of truth.

## Requirements

- WordPress 7.0 or newer (test targets: 7.0.6 and 7.1.2)
- PHP 7.4 or newer (PHP 8.3 recommended)
- Node.js 24.18 (pinned in `.nvmrc`) and Composer 2 for development

## Development

```bash
npm install
composer install
npm run build
npm run test:unit
composer test
```

Run the WordPress environment with `npm run env:start`. The plugin is mounted automatically by `.wp-env.json`.

## Architecture

- `includes/Domain`: navigation/node value objects and invariant validation.
- `includes/Adapters`: classic-menu and Navigation block conversion adapters.
- `includes/Infrastructure`: schema, repositories, native persistence, and locking.
- `includes/REST`: versioned REST controllers with capability checks and bounded request validation.
- `includes/Frontend`: conditional rendering, server-rendered navigation and progressive enhancement.
- `src`: TypeScript React admin application with command-based tree editing and undo/redo.

Detailed user, architecture, API, requirements, testing, and release documentation lives in [`docs/`](docs/).

## Privacy

Navigation Studio includes no telemetry and sends no data to third parties. External link checks are disabled by default and require an explicit administrator opt-in.

## License

GPL-2.0-or-later.
