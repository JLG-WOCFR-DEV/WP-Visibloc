/**
 * Playwright tests covering advanced visibility rules.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
    ensureExpertMode,
    canvasAwareGetByRole,
    canvasAwareGetByLabel,
} from '../utils/editor.js';

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

async function insertParagraphInGroup( editor, parentClientId, content ) {
    await editor.insertBlock(
        {
            name: 'core/paragraph',
            attributes: {
                content,
            },
        },
        { clientId: parentClientId },
    );
}

async function configureFallbackText( page, editor, text ) {
    const fallbackTab = canvasAwareGetByRole( page, editor, 'tab', {
        name: /Étape \d+ · Repli/,
    } );
    await expect( fallbackTab ).toBeVisible( { timeout: 20000 } );
    await fallbackTab.click( { timeout: 20000 } );

    const fallbackToggle = canvasAwareGetByRole( page, editor, 'checkbox', {
        name: 'Activer le repli pour ce bloc',
    } );
    await expect( fallbackToggle ).toBeVisible( { timeout: 20000 } );

    if ( !( await fallbackToggle.isChecked() ) ) {
        await fallbackToggle.check( { timeout: 20000 } );
    }

    const sourceSelect = canvasAwareGetByLabel( page, editor, 'Source du repli' );
    await expect( sourceSelect ).toBeVisible( { timeout: 20000 } );
    await sourceSelect.selectOption( 'text' );
    const textarea = canvasAwareGetByLabel( page, editor, 'Texte affiché en repli' );
    await expect( textarea ).toBeVisible( { timeout: 20000 } );
    await textarea.fill( '' );
    await textarea.type( text );
}

async function addAdvancedRule( page, editor ) {
    await ensureExpertMode( page, editor );

    const advancedTab = canvasAwareGetByRole( page, editor, 'tab', {
        name: /Étape 4.*Règles avancées/i,
    } );
    await expect( advancedTab ).toBeVisible( { timeout: 20000 } );
    await advancedTab.click( { timeout: 20000 } );

    const addRuleButton = canvasAwareGetByRole( page, editor, 'button', {
        name: 'Ajouter une règle de…',
    } );
    await expect( addRuleButton ).toBeVisible( { timeout: 20000 } );
    await expect( addRuleButton ).toBeEnabled();
    await addRuleButton.click( { timeout: 20000 } );

    const contentTypeMenuItem = canvasAwareGetByRole( page, editor, 'menuitem', {
        name: 'Type de contenu',
    } );
    await expect( contentTypeMenuItem ).toBeVisible( { timeout: 20000 } );
    await contentTypeMenuItem.click( { timeout: 20000 } );
}

async function getPostPermalink( page ) {
    return page.evaluate( () => {
        const store = window.wp.data.select( 'core/editor' );

        if ( ! store || typeof store.getPermalink !== 'function' ) {
            return '';
        }

        return store.getPermalink();
    } );
}

async function openFrontEndAsGuest( page, url ) {
    const guestContext = await page.context().browser().newContext();
    const guestPage = await guestContext.newPage();

    await guestPage.goto( url, { waitUntil: 'networkidle' } );

    return { guestContext, guestPage };
}

async function prepareGroupBlock( { admin, editor, page }, content ) {
    await admin.createNewPost();
    await editor.insertBlock( { name: 'core/group' } );
    const clientId = await selectBlockInEditor( page, 'core/group' );
    expect( clientId ).toBeTruthy();

    await insertParagraphInGroup( editor, clientId, content );
    await selectBlockInEditor( page, 'core/group' );

    const settingsButton = canvasAwareGetByRole( page, editor, 'button', {
        name: 'Settings',
        exact: true,
    } );
    await expect( settingsButton ).toBeVisible( { timeout: 20000 } );
    await settingsButton.click( { timeout: 20000 } );

    await ensureExpertMode( page, editor );

    return clientId;
}

test.describe( 'Advanced visibility rules', () => {
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

    test( 'logged-in status rule hides block for authenticated visitors', async ( {
        admin,
        editor,
        page,
    } ) => {
        const visibleText = 'Visible for logged out users';
        const fallbackText = 'Fallback for logged-in users';

        await prepareGroupBlock( { admin, editor, page }, visibleText );
        await addAdvancedRule( page, editor );

        const typeSelect = canvasAwareGetByLabel( page, editor, 'Type de règle' ).last();
        await typeSelect.selectOption( 'logged_in_status' );

        await canvasAwareGetByLabel( page, editor, 'Condition' )
            .last()
            .selectOption( 'is' );
        await canvasAwareGetByLabel( page, editor, 'État de connexion' )
            .last()
            .selectOption( 'logged_out' );

        await configureFallbackText( page, editor, fallbackText );

        await editor.publishPost();
        const permalink = await getPostPermalink( page );
        expect( permalink ).toMatch( /^https?:/ );

        await page.goto( permalink, { waitUntil: 'networkidle' } );
        await expect( page.locator( `text=${ fallbackText }` ) ).toBeVisible();
        await expect( page.locator( `text=${ visibleText }` ) ).toHaveCount( 0 );

        const { guestContext, guestPage } = await openFrontEndAsGuest( page, permalink );
        await expect( guestPage.locator( `text=${ visibleText }` ) ).toBeVisible();
        await expect( guestPage.locator( `text=${ fallbackText }` ) ).toHaveCount( 0 );
        await guestContext.close();
    } );

    test( 'role group rule restricts block to administrators', async ( {
        admin,
        editor,
        page,
    } ) => {
        const visibleText = 'Visible for administrators';
        const fallbackText = 'Fallback for non-admin users';

        await prepareGroupBlock( { admin, editor, page }, visibleText );
        await addAdvancedRule( page, editor );

        const typeSelect = canvasAwareGetByLabel( page, editor, 'Type de règle' ).last();
        await typeSelect.selectOption( 'user_role_group' );

        await canvasAwareGetByLabel( page, editor, 'Condition' )
            .last()
            .selectOption( 'matches' );
        await canvasAwareGetByLabel( page, editor, 'Groupe de rôles' )
            .last()
            .selectOption( 'administrator' );

        await configureFallbackText( page, editor, fallbackText );

        await editor.publishPost();
        const permalink = await getPostPermalink( page );
        expect( permalink ).toMatch( /^https?:/ );

        await page.goto( permalink, { waitUntil: 'networkidle' } );
        await expect( page.locator( `text=${ visibleText }` ) ).toBeVisible();
        await expect( page.locator( `text=${ fallbackText }` ) ).toHaveCount( 0 );

        const { guestContext, guestPage } = await openFrontEndAsGuest( page, permalink );
        await expect( guestPage.locator( `text=${ fallbackText }` ) ).toBeVisible();
        await expect( guestPage.locator( `text=${ visibleText }` ) ).toHaveCount( 0 );
        await guestContext.close();
    } );

    test( 'query parameter rule toggles block content', async ( {
        admin,
        editor,
        page,
    } ) => {
        const visibleText = 'Visible with promo parameter';
        const fallbackText = 'Fallback when promo missing';

        await prepareGroupBlock( { admin, editor, page }, visibleText );
        await addAdvancedRule( page, editor );

        const typeSelect = canvasAwareGetByLabel( page, editor, 'Type de règle' ).last();
        await typeSelect.selectOption( 'query_param' );

        await canvasAwareGetByLabel( page, editor, 'Condition' )
            .last()
            .selectOption( 'equals' );
        const paramInput = canvasAwareGetByLabel( page, editor, 'Nom du paramètre' ).last();
        await paramInput.fill( 'promo' );
        const valueInput = canvasAwareGetByLabel( page, editor, 'Valeur attendue' ).last();
        await valueInput.fill( 'special' );

        await configureFallbackText( page, editor, fallbackText );

        await editor.publishPost();
        const permalink = await getPostPermalink( page );
        expect( permalink ).toMatch( /^https?:/ );

        await page.goto( permalink, { waitUntil: 'networkidle' } );
        await expect( page.locator( `text=${ fallbackText }` ) ).toBeVisible();
        await expect( page.locator( `text=${ visibleText }` ) ).toHaveCount( 0 );

        const withParam = `${ permalink }${ permalink.includes( '?' ) ? '&' : '?' }promo=special`;
        await page.goto( withParam, { waitUntil: 'networkidle' } );
        await expect( page.locator( `text=${ visibleText }` ) ).toBeVisible();
        await expect( page.locator( `text=${ fallbackText }` ) ).toHaveCount( 0 );
    } );
} );
