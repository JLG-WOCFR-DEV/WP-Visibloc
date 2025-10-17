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

test.describe( 'Assistant Onboarding', () => {
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

    test( 'sélection d’une recette et enregistrement du brouillon', async ( { admin, editor, page } ) => {
        await admin.createNewPost();
        await editor.insertBlock( { name: 'core/group' } );

        const settingsButton = page.getByRole( 'button', {
            name: 'Settings',
            exact: true,
        } );
        await expect( settingsButton ).toBeVisible( { timeout: 20000 } );
        await settingsButton.click( { timeout: 20000 } );

        await ensureExpertMode( page );

        const onboardingButton = page.getByRole( 'button', {
            name: 'Assistant Onboarding',
            exact: true,
        } );
        await expect( onboardingButton ).toBeVisible( { timeout: 20000 } );
        await onboardingButton.click( { timeout: 20000 } );

        const openWizardButton = page.getByRole( 'button', {
            name: /^(Ouvrir|Reprendre) l’assistant$/,
        } );
        await expect( openWizardButton ).toBeVisible( { timeout: 20000 } );
        await expect( openWizardButton ).toBeEnabled();

        await openWizardButton.click( { timeout: 20000 } );
        const modal = page.getByRole( 'dialog', { name: 'Assistant visibilité – Onboarding' } );
        await expect( modal ).toBeVisible( { timeout: 20000 } );

        const recipeSelect = modal.getByLabel( 'Bibliothèque de recettes' );
        await expect( recipeSelect ).toBeVisible( { timeout: 20000 } );
        await recipeSelect.selectOption( 'abandoned-cart' );

        await expect( modal.getByLabel( 'Segment principal' ) ).toHaveValue(
            /Clients identifiés avec un panier actif/
        );

        const timingTab = modal.getByRole( 'button', { name: 'Étape 3 · Timing' } );
        await expect( timingTab ).toBeVisible( { timeout: 20000 } );
        await timingTab.click( { timeout: 20000 } );
        await expect( modal.getByLabel( 'Cadence' ) ).toHaveValue( '2 rappels maximum.' );

        const saveButton = modal.getByRole( 'button', { name: 'Enregistrer le brouillon' } );
        await expect( saveButton ).toBeVisible( { timeout: 20000 } );
        await expect( saveButton ).toBeEnabled();
        await saveButton.click( { timeout: 20000 } );
        await expect( modal.getByRole( 'button', { name: 'Enregistrement…' } ) ).toBeVisible( {
            timeout: 20000,
        } );
        await expect( saveButton ).toHaveText( 'Enregistrer le brouillon' );

        const closeButton = modal.getByRole( 'button', { name: 'Close' } );
        await expect( closeButton ).toBeVisible( { timeout: 20000 } );
        await closeButton.click( { timeout: 20000 } );

        await expect( page.getByText( /Recette sélectionnée/ ) ).toContainText( 'Relance panier' );
        await expect( page.getByText( /Brouillon mis à jour/ ) ).toBeVisible();
    } );
} );
