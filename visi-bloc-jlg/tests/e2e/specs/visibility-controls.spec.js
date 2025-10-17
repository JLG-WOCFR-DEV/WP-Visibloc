/**
 * @wordpress/e2e-test-utils-playwright based regression test covering the
 * Visi-Bloc editor experience.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const PLUGIN_SLUG = 'visi-bloc-jlg';

async function ensureExpertMode( page ) {
    const modeToggle = page.locator( '.visibloc-editor-mode__toggle' );

    await expect( modeToggle ).toBeVisible( { timeout: 20000 } );

    const expertButton = modeToggle.getByRole( 'button', { name: 'Expert', exact: true } );

    if ( await expertButton.count() ) {
        const isPressed = await expertButton.getAttribute( 'aria-pressed' );

        if ( isPressed !== 'true' ) {
            await expertButton.click( { timeout: 20000 } );
        }

        return;
    }

    const expertRadio = modeToggle.getByRole( 'radio', { name: 'Expert', exact: true } );

    if ( await expertRadio.count() ) {
        if ( !( await expertRadio.isChecked() ) ) {
            await expertRadio.check( { timeout: 20000 } );
        }

        return;
    }

    throw new Error( 'Expert mode toggle not found in the inspector controls.' );
}

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
    await expect( hideButton ).toBeVisible( { timeout: 20000 } );
    await expect( hideButton ).toBeEnabled();
    await hideButton.click( { timeout: 20000 } );
    await expect( blockLocator ).toHaveClass( /bloc-editeur-cache/ );

    const listViewToggle = page.getByRole( 'button', { name: 'List view' } );
    await expect( listViewToggle ).toBeVisible( { timeout: 20000 } );
    await listViewToggle.click( { timeout: 20000 } );
    const listViewRow = page.locator( `.block-editor-list-view__block[data-block="${ clientId }"]` );
    await expect( listViewRow ).toHaveClass( /bloc-editeur-cache/ );

    await expect( showButton ).toBeVisible( { timeout: 20000 } );
    await expect( showButton ).toBeEnabled();
    await showButton.click( { timeout: 20000 } );
    await expect( blockLocator ).not.toHaveClass( /bloc-editeur-cache/ );
    await expect( listViewRow ).not.toHaveClass( /bloc-editeur-cache/ );

    await listViewToggle.click( { timeout: 20000 } );

    const settingsButton = page.getByRole( 'button', {
        name: 'Settings',
        exact: true,
    } );
    await expect( settingsButton ).toBeVisible( { timeout: 20000 } );
    await settingsButton.click( { timeout: 20000 } );

    await ensureExpertMode( page );

    const getStepTab = ( label ) => page.getByRole( 'tab', { name: label } );
    const stepSummary = page.locator( '.visi-bloc-help-text.is-summary' );

    const step1Tab = getStepTab( 'Étape 1 · Appareils' );
    await expect( step1Tab ).toBeVisible( { timeout: 20000 } );
    await step1Tab.click( { timeout: 20000 } );
    const deviceToggleGroups = page.locator( '.visi-bloc-device-toggle-group' );
    await expect( deviceToggleGroups.first() ).toBeVisible( { timeout: 20000 } );
    await deviceToggleGroups
        .first()
        .getByRole( 'button', { name: 'Desktop' } )
        .click( { timeout: 20000 } );
    await expect( stepSummary ).toHaveText( 'Afficher uniquement – Desktop' );

    const step2Tab = getStepTab( 'Étape 2 · Calendrier' );
    await step2Tab.click( { timeout: 20000 } );

    const schedulingToggle = page.getByRole( 'checkbox', { name: 'Activer la programmation' } );
    await schedulingToggle.check( { timeout: 20000 } );
    await expect( stepSummary ).toHaveText( 'Programmation active' );

    const startToggle = page.getByRole( 'checkbox', { name: 'Définir une date de début' } );
    await startToggle.check( { timeout: 20000 } );
    await expect( stepSummary ).toHaveText( 'Date définie' );

    const startPicker = page.locator( '.visi-bloc-datepicker-wrapper' ).first();
    await startPicker.getByLabel( 'Date' ).fill( '2030-06-15' );
    await startPicker.getByLabel( 'Time' ).fill( '08:30' );

    const endToggle = page.getByRole( 'checkbox', { name: 'Définir une date de fin' } );
    await endToggle.check( { timeout: 20000 } );
    const endPicker = page.locator( '.visi-bloc-datepicker-wrapper' ).nth( 1 );
    await endPicker.getByLabel( 'Date' ).fill( '2030-06-20' );
    await endPicker.getByLabel( 'Time' ).fill( '17:45' );
    await expect( stepSummary ).toHaveText( 'Plage définie' );

    await getStepTab( 'Étape 3 · Rôles' ).click( { timeout: 20000 } );

    const loggedInCheckbox = page.getByRole( 'checkbox', { name: 'Utilisateurs connectés (tous)' } );
    await loggedInCheckbox.check( { timeout: 20000 } );

    const administratorCheckbox = page.getByRole( 'checkbox', { name: 'Administrator' } );
    await administratorCheckbox.check( { timeout: 20000 } );
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

    await listViewToggle.click( { timeout: 20000 } );
    await expect( listViewRow ).toHaveClass( /bloc-editeur-conditionnel/ );
    await listViewToggle.click( { timeout: 20000 } );

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

        await expect( columnsCheckbox ).toBeVisible( { timeout: 20000 } );
        await columnsCheckbox.check( { timeout: 20000 } );

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

        const settingsButton = page.getByRole( 'button', {
            name: 'Settings',
            exact: true,
        } );
        await expect( settingsButton ).toBeVisible( { timeout: 20000 } );
        await settingsButton.click( { timeout: 20000 } );

        await ensureExpertMode( page );

        const selectStep = async ( name ) => {
            const tab = page.getByRole( 'tab', { name } );
            await expect( tab ).toBeVisible( { timeout: 20000 } );
            await tab.click( { timeout: 20000 } );
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
            .click( { timeout: 20000 } );
        await expect( stepSummary ).toHaveText( 'Afficher uniquement – Desktop' );

        await selectStep( 'Étape 2 · Calendrier' );
        const schedulingToggle = page.getByRole( 'checkbox', { name: 'Activer la programmation' } );
        await schedulingToggle.check();
        await expect( stepSummary ).toHaveText( 'Programmation active' );

        await page
            .getByRole( 'checkbox', { name: 'Définir une date de début' } )
            .check( { timeout: 20000 } );
        await expect( stepSummary ).toHaveText( 'Date définie' );

        await page
            .getByRole( 'checkbox', { name: 'Définir une date de fin' } )
            .check( { timeout: 20000 } );
        await expect( stepSummary ).toHaveText( 'Plage définie' );

        await selectStep( 'Étape 3 · Rôles' );
        await page
            .getByRole( 'checkbox', { name: 'Visiteurs déconnectés' } )
            .check( { timeout: 20000 } );
        await page
            .getByRole( 'checkbox', { name: 'Utilisateurs connectés (tous)' } )
            .check( { timeout: 20000 } );
        await expect( stepSummary ).toHaveText( '2 rôles' );

        await selectStep( 'Étape 4 · Règles avancées' );
        const addRuleButton = page.getByRole( 'button', { name: 'Ajouter une règle de…' } );
        await expect( addRuleButton ).toBeVisible( { timeout: 20000 } );
        await expect( addRuleButton ).toBeEnabled();
        await addRuleButton.click( { timeout: 20000 } );
        const ruleMenuItem = page.getByRole( 'menuitem', { name: 'Type de contenu' } );
        await expect( ruleMenuItem ).toBeVisible( { timeout: 20000 } );
        await ruleMenuItem.click( { timeout: 20000 } );
        await expect( stepSummary ).toHaveText( '1 règle ET' );

        await selectStep( /Étape \d+ · Repli/ );
        const fallbackToggle = page.getByRole( 'checkbox', { name: 'Activer le repli pour ce bloc' } );
        await expect( fallbackToggle ).toBeVisible( { timeout: 20000 } );
        await fallbackToggle.uncheck( { timeout: 20000 } );
        await expect( stepSummary ).toHaveText( 'Inactif' );
    } );
} );
