import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Navigation Studio', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.visitAdminPage( 'admin.php', 'page=navigation-studio' );
	} );

	test( 'creates and opens a classic navigation', async ( { page } ) => {
		await expect(
			page.getByRole( 'heading', { name: 'Navigation Studio' } )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Create navigation' } ).click();
		await page.getByLabel( 'Name' ).fill( 'Primary test navigation' );
		await page.getByRole( 'button', { name: 'Create and edit' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Navigation structure' } )
		).toBeVisible();
		await expect(
			page.getByText( 'This navigation is empty' )
		).toBeVisible();
	} );

	test( 'opens the command palette from the keyboard', async ( { page } ) => {
		await page.getByRole( 'button', { name: 'Create navigation' } ).click();
		await page.getByLabel( 'Name' ).fill( 'Keyboard test navigation' );
		await page.getByRole( 'button', { name: 'Create and edit' } ).click();
		await page.keyboard.press( 'ControlOrMeta+K' );
		await expect(
			page.getByRole( 'dialog', { name: 'Command palette' } )
		).toBeVisible();
	} );
} );
