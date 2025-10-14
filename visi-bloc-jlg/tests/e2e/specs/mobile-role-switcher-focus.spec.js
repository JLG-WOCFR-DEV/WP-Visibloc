import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const PLUGIN_SLUG = 'visi-bloc-jlg';
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

async function getScrollLockState( page ) {
    return page.evaluate( () => {
        const html = document.documentElement;
        const body = document.body;

        if ( ! html || ! body ) {
            return {
                htmlLocked: false,
                htmlOverflowX: '',
                htmlOverflowY: '',
                bodyOverflowX: '',
                bodyOverflowY: '',
                htmlTouchAction: '',
                bodyTouchAction: '',
            };
        }

        const htmlStyle = window.getComputedStyle( html );
        const bodyStyle = window.getComputedStyle( body );
        const htmlTouchAction = htmlStyle.touchAction || htmlStyle[ 'touch-action' ] || '';
        const bodyTouchAction = bodyStyle.touchAction || bodyStyle[ 'touch-action' ] || '';

        return {
            htmlLocked: html.classList.contains( 'visibloc-role-switcher--locked' ),
            htmlOverflowX: htmlStyle.overflowX,
            htmlOverflowY: htmlStyle.overflowY,
            bodyOverflowX: bodyStyle.overflowX,
            bodyOverflowY: bodyStyle.overflowY,
            htmlTouchAction,
            bodyTouchAction,
        };
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

        const scrollLockState = await getScrollLockState( page );
        expect( scrollLockState.htmlLocked ).toBe( true );
        expect( scrollLockState.htmlOverflowY ).toBe( 'hidden' );
        expect( scrollLockState.bodyOverflowY ).toBe( 'hidden' );
        expect( scrollLockState.htmlTouchAction ).toBe( 'none' );
        expect( scrollLockState.bodyTouchAction ).toBe( 'none' );

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

        const scrollLockStateAfterClose = await getScrollLockState( page );
        expect( scrollLockStateAfterClose.htmlLocked ).toBe( false );
        expect( scrollLockStateAfterClose.htmlOverflowY ).not.toBe( 'hidden' );
        expect( scrollLockStateAfterClose.bodyOverflowY ).not.toBe( 'hidden' );
        expect( scrollLockStateAfterClose.htmlTouchAction ).not.toBe( 'none' );
        expect( scrollLockStateAfterClose.bodyTouchAction ).not.toBe( 'none' );

        await toggle.click();
        await expect( panel ).toBeVisible();

        const scrollLockStateAfterReopen = await getScrollLockState( page );
        expect( scrollLockStateAfterReopen.htmlLocked ).toBe( true );

        await page.evaluate( () => {
            document.body.dispatchEvent( new MouseEvent( 'click', { bubbles: true } ) );
        } );

        await expect( panel ).toBeHidden();

        const scrollLockStateAfterOutsideClick = await getScrollLockState( page );
        expect( scrollLockStateAfterOutsideClick.htmlLocked ).toBe( false );
    } );
} );
