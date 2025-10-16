/**
 * @wordpress/e2e-test-utils-playwright based regression test covering the
 * Visi-Bloc editor experience.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const PLUGIN_SLUG = 'visi-bloc-jlg';

async function selectBlockInEditor( page, blockName ) {
    const clientIdHandle = await page.waitForFunction(
        ( targetName ) => {
            const { select, dispatch } = window.wp.data;
            const editorStore = select( 'core/block-editor' );

            if ( ! editorStore || typeof editorStore.getBlocks !== 'function' ) {
                return false;
            }

            const findBlock = ( blocks ) => {
                if ( ! Array.isArray( blocks ) ) {
                    return null;
                }

                for ( const block of blocks ) {
                    if ( ! block ) {
                        continue;
                    }

                    if ( block.name === targetName ) {
                        return block;
                    }

                    if ( Array.isArray( block.innerBlocks ) && block.innerBlocks.length > 0 ) {
                        const nestedMatch = findBlock( block.innerBlocks );

                        if ( nestedMatch ) {
                            return nestedMatch;
                        }
                    }
                }

                return null;
            };

            const match = findBlock( editorStore.getBlocks() );

            if ( ! match ) {
                return false;
            }

            const editorDispatch = dispatch( 'core/block-editor' );

            if ( editorDispatch && typeof editorDispatch.selectBlock === 'function' ) {
                editorDispatch.selectBlock( match.clientId );
            }

            return match.clientId;
        },
        blockName,
    );

    return clientIdHandle.jsonValue();
}

async function exerciseVisibilityControls( { admin, editor, page }, blockName ) {
    await admin.createNewPost();
    await editor.insertBlock( { name: blockName } );

    const clientId = await selectBlockInEditor( page, blockName );
    expect( clientId ).toBeTruthy();

    const blockLocator = page
        .locator( `.block-editor-block-list__block[data-block="${ clientId }"]` )
        .first();

    await expect( blockLocator ).toBeVisible();
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

    await page.getByRole( 'button', { name: 'Settings', exact: true } ).click();

    const getStepTab = ( label ) => page.getByRole( 'tab', { name: label } );
    const stepSummary = page.locator( '.visi-bloc-help-text.is-summary' );

    await getStepTab( 'Étape 1 · Appareils' ).click();
    const deviceToggleGroups = page.locator( '.visi-bloc-device-toggle-group' );
    await expect( deviceToggleGroups.first() ).toBeVisible();
    await deviceToggleGroups
        .first()
        .getByRole( 'button', { name: 'Desktop' } )
        .click();
    await expect( stepSummary ).toHaveText( 'Afficher uniquement – Desktop' );

    await getStepTab( 'Étape 2 · Calendrier' ).click();

    const schedulingToggle = page.getByRole( 'checkbox', { name: 'Activer la programmation' } );
    await schedulingToggle.check();
    await expect( stepSummary ).toHaveText( 'Programmation active' );

    const startToggle = page.getByRole( 'checkbox', { name: 'Définir une date de début' } );
    await startToggle.check();
    await expect( stepSummary ).toHaveText( 'Date définie' );

    const startPicker = page.locator( '.visi-bloc-datepicker-wrapper' ).first();
    await startPicker.getByLabel( 'Date' ).fill( '2030-06-15' );
    await startPicker.getByLabel( 'Time' ).fill( '08:30' );

    const endToggle = page.getByRole( 'checkbox', { name: 'Définir une date de fin' } );
    await endToggle.check();
    const endPicker = page.locator( '.visi-bloc-datepicker-wrapper' ).nth( 1 );
    await endPicker.getByLabel( 'Date' ).fill( '2030-06-20' );
    await endPicker.getByLabel( 'Time' ).fill( '17:45' );
    await expect( stepSummary ).toHaveText( 'Plage définie' );

    await getStepTab( 'Étape 3 · Rôles' ).click();

    const loggedInCheckbox = page.getByRole( 'checkbox', { name: 'Utilisateurs connectés (tous)' } );
    await loggedInCheckbox.check();

    const administratorCheckbox = page.getByRole( 'checkbox', { name: 'Administrator' } );
    await administratorCheckbox.check();
    await expect( stepSummary ).toHaveText( '2 rôles' );

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

    await expect( blockLocator ).toHaveClass( /bloc-editeur-conditionnel/ );

    await page.getByRole( 'button', { name: 'List view' } ).click();
    await expect( listViewRow ).toHaveClass( /bloc-editeur-conditionnel/ );
    await page.getByRole( 'button', { name: 'List view' } ).click();

    const postContent = await editor.getEditedPostContent();
    expect( postContent ).toContain( `<!-- wp:${ blockName }` );
    expect( postContent ).toContain( 'vb-desktop-only' );
    expect( postContent ).toContain( '"visibilityRoles":["logged-in","administrator"' );
}

test.describe( 'Visi-Bloc group visibility controls', () => {
    test.beforeEach( async ( { requestUtils } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
        await requestUtils.rest( {
            method: 'POST',
            path: '/wp/v2/settings',
            data: {
                visibloc_supported_blocks: [],
            },
        } );
    } );

    test( 'toolbar, scheduling and inspector controls update group block attributes', async ( {
        admin,
        editor,
        page,
    } ) => {
        await exerciseVisibilityControls( { admin, editor, page }, 'core/group' );
    } );

    test( 'newly enabled blocks expose visibility controls in the editor', async ( {
        admin,
        editor,
        page,
    } ) => {
        await admin.visitAdminPage( 'admin.php', 'page=visi-bloc-jlg-help' );

        const columnsCheckbox = page
            .locator( '.visibloc-supported-blocks-item' )
            .filter( { hasText: '(core/columns)' } )
            .locator( 'input[type="checkbox"]' );

        await columnsCheckbox.check();

        await Promise.all( [
            page.waitForNavigation( { waitUntil: 'networkidle' } ),
            page.getByRole( 'button', { name: 'Enregistrer les blocs compatibles' } ).click(),
        ] );

        await expect( page.getByText( 'Réglages mis à jour.' ) ).toBeVisible();

        await exerciseVisibilityControls( { admin, editor, page }, 'core/columns' );
    } );

    test( 'inspector panels display configuration summaries', async ( { admin, editor, page } ) => {
        await admin.createNewPost();
        await editor.insertBlock( { name: 'core/group' } );

        const clientId = await selectBlockInEditor( page, 'core/group' );
        expect( clientId ).toBeTruthy();

        await page.getByRole( 'button', { name: 'Settings', exact: true } ).click();

        const selectStep = async ( name ) => {
            await page.getByRole( 'tab', { name } ).click();
        };
        const stepSummary = page.locator( '.visi-bloc-help-text.is-summary' );

        await selectStep( 'Étape 1 · Appareils' );
        await expect( stepSummary ).toHaveText( 'Inactif' );

        await selectStep( 'Étape 2 · Calendrier' );
        await expect( stepSummary ).toHaveText( 'Inactif' );

        await selectStep( 'Étape 3 · Rôles' );
        await expect( stepSummary ).toHaveText( 'Inactif' );

        await selectStep( 'Étape 4 · Règles avancées' );
        await expect( stepSummary ).toHaveText( 'Inactif' );

        await selectStep( /Étape \d+ · Repli/ );
        await expect( stepSummary ).not.toHaveText( 'Inactif' );

        await selectStep( 'Étape 1 · Appareils' );
        const visibilityGroups = page.locator( '.visi-bloc-device-toggle-group' );
        await visibilityGroups
            .first()
            .getByRole( 'button', { name: 'Desktop' } )
            .click();
        await expect( stepSummary ).toHaveText( 'Afficher uniquement – Desktop' );

        await selectStep( 'Étape 2 · Calendrier' );
        const schedulingToggle = page.getByRole( 'checkbox', { name: 'Activer la programmation' } );
        await schedulingToggle.check();
        await expect( stepSummary ).toHaveText( 'Programmation active' );

        await page.getByRole( 'checkbox', { name: 'Définir une date de début' } ).check();
        await expect( stepSummary ).toHaveText( 'Date définie' );

        await page.getByRole( 'checkbox', { name: 'Définir une date de fin' } ).check();
        await expect( stepSummary ).toHaveText( 'Plage définie' );

        await selectStep( 'Étape 3 · Rôles' );
        await page.getByRole( 'checkbox', { name: 'Visiteurs déconnectés' } ).check();
        await page.getByRole( 'checkbox', { name: 'Utilisateurs connectés (tous)' } ).check();
        await expect( stepSummary ).toHaveText( '2 rôles' );

        await selectStep( 'Étape 4 · Règles avancées' );
        await page.getByRole( 'button', { name: 'Ajouter une règle de…' } ).click();
        await page.getByRole( 'menuitem', { name: 'Type de contenu' } ).click();
        await expect( stepSummary ).toHaveText( '1 règle ET' );

        await selectStep( /Étape \d+ · Repli/ );
        const fallbackToggle = page.getByRole( 'checkbox', { name: 'Activer le repli pour ce bloc' } );
        await fallbackToggle.uncheck();
        await expect( stepSummary ).toHaveText( 'Inactif' );
    } );
} );
