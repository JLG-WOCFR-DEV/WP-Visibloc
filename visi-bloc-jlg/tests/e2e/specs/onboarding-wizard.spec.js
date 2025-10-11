import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const PLUGIN_SLUG = 'visi-bloc-jlg/visi-bloc-jlg.php';

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

        await page.getByRole( 'button', { name: 'Settings' } ).click();
        const openWizardButton = page.getByRole( 'button', { name: 'Ouvrir l’assistant' } );
        await expect( openWizardButton ).toBeVisible();

        await openWizardButton.click();
        const modal = page.getByRole( 'dialog', { name: 'Assistant visibilité – Onboarding' } );
        await expect( modal ).toBeVisible();

        const recipeSelect = modal.getByLabel( 'Bibliothèque de recettes' );
        await recipeSelect.selectOption( 'abandoned-cart' );

        await expect( modal.getByLabel( 'Segment principal' ) ).toHaveValue(
            /Clients identifiés avec un panier actif/
        );

        await modal.getByRole( 'button', { name: 'Étape 3 · Timing' } ).click();
        await expect( modal.getByLabel( 'Cadence' ) ).toHaveValue( '2 rappels maximum.' );

        const saveButton = modal.getByRole( 'button', { name: 'Enregistrer le brouillon' } );
        await saveButton.click();
        await expect( modal.getByRole( 'button', { name: 'Enregistrement…' } ) ).toBeVisible();
        await expect( saveButton ).toHaveText( 'Enregistrer le brouillon' );

        await modal.getByRole( 'button', { name: 'Close' } ).click();

        await expect( page.getByText( /Recette sélectionnée/ ) ).toContainText( 'Relance panier' );
        await expect( page.getByText( /Brouillon mis à jour/ ) ).toBeVisible();
    } );
} );
