/**
 * @wordpress/e2e-test-utils-playwright based regression test covering the
 * Visi-Bloc editor experience.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const GROUP_BLOCK_NAME = 'core/group';
const PLUGIN_SLUG = 'visi-bloc-jlg/visi-bloc-jlg.php';

async function getSelectedGroupClientId( page ) {
    return page.evaluate( () => {
        const store = window.wp.data.select( 'core/block-editor' );
        const clientId = store.getSelectedBlockClientId();

        if ( ! clientId ) {
            return null;
        }

        const block = store.getBlock( clientId );

        return block && block.name === 'core/group' ? clientId : null;
    } );
}

test.describe( 'Visi-Bloc group visibility controls', () => {
    test.beforeEach( async ( { requestUtils } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
    } );

    test( 'toolbar, scheduling and inspector controls update block attributes', async ( {
        admin,
        editor,
        page,
    } ) => {
        await admin.createNewPost();
        await editor.insertBlock( { name: GROUP_BLOCK_NAME } );

        const clientId = await getSelectedGroupClientId( page );
        expect( clientId ).toBeTruthy();

        const blockLocator = page.locator( `.block-editor-block-list__block[data-block="${ clientId }"]` );

        await expect( blockLocator ).not.toHaveClass( /bloc-editeur-cache/ );

        const hideButton = page.getByRole( 'button', { name: 'Rendre caché' } );
        const showButton = page.getByRole( 'button', { name: 'Rendre visible' } );

        await hideButton.click();
        await expect( blockLocator ).toHaveClass( /bloc-editeur-cache/ );

        await page.getByRole( 'button', { name: 'List view' } ).click();
        const listViewRow = page.locator( `.block-editor-list-view__block[data-block="${ clientId }"]` );
        await expect( listViewRow ).toHaveClass( /bloc-editeur-cache/ );

        await showButton.click();
        await expect( blockLocator ).not.toHaveClass( /bloc-editeur-cache/ );
        await expect( listViewRow ).not.toHaveClass( /bloc-editeur-cache/ );

        await page.getByRole( 'button', { name: 'List view' } ).click();

        await page.getByRole( 'button', { name: 'Settings' } ).click();

        const deviceSelect = page.getByLabel( 'Visibilité par Appareil' );
        await deviceSelect.selectOption( 'desktop-only' );

        await page.getByRole( 'button', { name: 'Programmation' } ).click();

        const schedulingToggle = page.getByRole( 'checkbox', { name: 'Activer la programmation' } );
        await schedulingToggle.check();

        const startToggle = page.getByRole( 'checkbox', { name: 'Définir une date de début' } );
        await startToggle.check();

        const startPicker = page.locator( '.visi-bloc-datepicker-wrapper' ).first();
        await startPicker.getByLabel( 'Date' ).fill( '2030-06-15' );
        await startPicker.getByLabel( 'Time' ).fill( '08:30' );

        const endToggle = page.getByRole( 'checkbox', { name: 'Définir une date de fin' } );
        await endToggle.check();

        const endPicker = page.locator( '.visi-bloc-datepicker-wrapper' ).nth( 1 );
        await endPicker.getByLabel( 'Date' ).fill( '2030-06-20' );
        await endPicker.getByLabel( 'Time' ).fill( '17:45' );

        await page.getByRole( 'button', { name: 'Visibilité par Rôle' } ).click();

        const loggedInCheckbox = page.getByRole( 'checkbox', { name: 'Utilisateurs Connectés (tous)' } );
        await loggedInCheckbox.check();

        const administratorCheckbox = page.getByRole( 'checkbox', { name: 'Administrator' } );
        await administratorCheckbox.check();

        const attributes = await page.evaluate( ( id ) => {
            const store = window.wp.data.select( 'core/block-editor' );
            const block = store.getBlock( id );

            return block ? block.attributes : {};
        }, clientId );

        expect( attributes.isHidden ).toBe( false );
        expect( attributes.deviceVisibility ).toBe( 'desktop-only' );
        expect( attributes.isSchedulingEnabled ).toBe( true );
        expect( attributes.publishStartDate ).toContain( '2030-06-15T08:30' );
        expect( attributes.publishEndDate ).toContain( '2030-06-20T17:45' );
        expect( attributes.visibilityRoles ).toEqual( expect.arrayContaining( [ 'logged-in', 'administrator' ] ) );

        const postContent = await editor.getEditedPostContent();
        expect( postContent ).toContain( 'vb-desktop-only' );
        expect( postContent ).toContain( '"visibilityRoles":["logged-in","administrator"' );
    } );
} );
