# Testing and release

The supported matrix is WordPress 7.0.6 and 7.1.2 with PHP 7.4, 8.4, and 8.5. PHP 8.4 is the recommended production runtime. Node 24.18 is pinned for the current test toolchain; runtime WordPress packages compile against the `wp-7.0` npm distribution tags.

Run local checks:

```bash
npm ci
composer install
npm run build
npm run lint:js
npm run lint:css
npm run test:unit
composer lint
composer phpstan
composer test
npm run env:start
npm run test:e2e
```

The E2E configuration runs Chromium, Firefox, and WebKit. WordPress integration tests use the test library exposed by `wp-env`. Run Plugin Check against the generated ZIP rather than only the source directory.

Build the package with `php tools/build-release.php`. It uses a fixed file allowlist and normalized ZIP timestamps, then writes `dist/navigation-studio-1.0.0.zip`. Install that exact ZIP into a clean environment for activation, deactivation/reactivation, upgrade, console, debug-log, frontend, and accessibility smoke tests.

Manual release review includes keyboard-only admin and frontend paths; NVDA/Firefox and VoiceOver/Safari semantics; RTL and 200% zoom; reduced motion; no-JavaScript links; touch drawer behavior; 0/1/100/500/1,000-node fixtures; and visual loading/empty/error/conflict states.
