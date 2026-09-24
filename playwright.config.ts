import { defineConfig, devices } from '@playwright/test';

export default defineConfig( {
	testDir: './specs',
	fullyParallel: false,
	forbidOnly: true,
	retries: 1,
	reporter: [
		[ 'list' ],
		[
			'html',
			{ outputFolder: 'artifacts/playwright-report', open: 'never' },
		],
	],
	use: {
		baseURL: 'http://localhost:8888',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{ name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } },
		{ name: 'firefox', use: { ...devices[ 'Desktop Firefox' ] } },
		{ name: 'webkit', use: { ...devices[ 'Desktop Safari' ] } },
	],
} );
