import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const PLUGIN_SLUG = 'visi-bloc-jlg/visi-bloc-jlg.php';
const FOCUSABLE_SELECTOR = [
    'button:not([disabled])',
    '[href]',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
]
    .map( ( selector ) => `${ selector }:not([aria-hidden="true"])` )
    .join( ', ' );

async function getManagedInertCount( page ) {
    return page.evaluate( () => {
        const switcher = document.querySelector('[data-visibloc-role-switcher]');

        if ( ! switcher ) {
            return 0;
        }

        return Array.from(
            document.querySelectorAll('[data-visibloc-role-switcher-inert="true"]'),
        ).filter( ( element ) => ! switcher.contains( element ) ).length;
    } );
}

test.describe( 'Visi-Bloc mobile role switcher focus management', () => {
    test.beforeEach( async ( { requestUtils } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
    } );

    test.afterEach( async ( { requestUtils } ) => {
        await requestUtils.deactivatePlugin( PLUGIN_SLUG );
    } );

    test( 'keeps focus trapped inside the panel while it is open', async ( { admin, page } ) => {
        await admin.visitAdminPage( 'index.php' );
        await page.goto( '/' );

        const switcher = page.locator( '[data-visibloc-role-switcher]' );
        await expect( switcher ).toBeVisible();

        const toggle = switcher.locator( '.visibloc-mobile-role-switcher__toggle' );
        await toggle.click();

        const panel = switcher.locator( '.visibloc-mobile-role-switcher__panel' );
        await expect( panel ).toBeVisible();
        await expect( panel ).toHaveAttribute( 'aria-hidden', 'false' );

        const focusable = panel.locator( FOCUSABLE_SELECTOR );
        const focusableCount = await focusable.count();
        expect( focusableCount ).toBeGreaterThan( 0 );

        const firstFocusable = focusable.first();
        const lastFocusable = focusable.last();

        await firstFocusable.focus();

        await page.keyboard.press( 'Shift+Tab' );
        await expect( lastFocusable ).toBeFocused();
        await expect( panel ).toBeVisible();

        await page.keyboard.press( 'Tab' );
        await expect( firstFocusable ).toBeFocused();
        await expect( panel ).toBeVisible();

        const inertCount = await getManagedInertCount( page );
        expect( inertCount ).toBeGreaterThan( 0 );

        await page.keyboard.press( 'Escape' );
        await expect( panel ).toBeHidden();
        await expect( toggle ).toBeFocused();

        const inertCountAfterClose = await getManagedInertCount( page );
        expect( inertCountAfterClose ).toBe( 0 );
    } );
} );
