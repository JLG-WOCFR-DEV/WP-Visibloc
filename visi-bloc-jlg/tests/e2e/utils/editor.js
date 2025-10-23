import { expect } from '@wordpress/e2e-test-utils-playwright';

function hasCanvas( editor ) {
    return Boolean( editor && editor.page && editor.canvas );
}

async function resolveCanvasAwareLocator( baseLocator, getCanvasLocator ) {
    if ( await baseLocator.count() ) {
        return baseLocator;
    }

    if ( typeof getCanvasLocator === 'function' ) {
        const canvasLocator = await getCanvasLocator();

        if ( canvasLocator ) {
            return canvasLocator;
        }
    }

    return baseLocator;
}

export async function canvasAwareLocator( page, editor, selector, options ) {
    const baseLocator = page.locator( selector, options );

    if ( hasCanvas( editor ) ) {
        return resolveCanvasAwareLocator( baseLocator, () =>
            editor.canvas.locator( selector, options )
        );
    }

    return baseLocator;
}

export async function canvasAwareGetByRole( page, editor, role, options ) {
    const baseLocator = page.getByRole( role, options );

    if ( hasCanvas( editor ) && typeof editor.canvas.getByRole === 'function' ) {
        return resolveCanvasAwareLocator( baseLocator, () =>
            editor.canvas.getByRole( role, options )
        );
    }

    return baseLocator;
}

export async function canvasAwareGetByLabel( page, editor, text, options ) {
    const baseLocator = page.getByLabel( text, options );

    if ( hasCanvas( editor ) && typeof editor.canvas.getByLabel === 'function' ) {
        return resolveCanvasAwareLocator( baseLocator, () =>
            editor.canvas.getByLabel( text, options )
        );
    }

    return baseLocator;
}

export async function canvasAwareGetByText( page, editor, text, options ) {
    const baseLocator = page.getByText( text, options );

    if ( hasCanvas( editor ) && typeof editor.canvas.getByText === 'function' ) {
        return resolveCanvasAwareLocator( baseLocator, () =>
            editor.canvas.getByText( text, options )
        );
    }

    return baseLocator;
}

async function ensureVisiblocPanelOpen( page ) {
    const panelToggle = page.getByRole( 'button', {
        name: 'Parcours de visibilité',
        exact: false,
    } );

    if ( ! ( await panelToggle.count() ) ) {
        return;
    }

    for ( let attempt = 0; attempt < 3; attempt++ ) {
        const isExpanded = await panelToggle.getAttribute( 'aria-expanded' );

        if ( isExpanded === 'true' ) {
            return;
        }

        await panelToggle.click( { timeout: 20000 } );

        try {
            await expect( panelToggle ).toHaveAttribute( 'aria-expanded', 'true', {
                timeout: 5000,
            } );
            return;
        } catch ( error ) {
            if ( attempt === 2 ) {
                throw error;
            }
        }
    }

    throw new Error( 'Unable to open the Visi-Bloc panel after multiple attempts.' );
}

export async function ensureExpertMode( page, editor ) {
    await ensureVisiblocPanelOpen( page );

    const modeToggle = (
        await canvasAwareLocator( page, editor, '.visibloc-editor-mode__toggle' )
    ).first();

    await expect( modeToggle ).toBeVisible( { timeout: 20000 } );

    const ensureExpertButtonSelected = async () => {
        const expertButton = modeToggle.getByRole( 'button', {
            name: 'Expert',
            exact: true,
        } );

        if ( ! ( await expertButton.count() ) ) {
            return false;
        }

        if ( ! ( await expertButton.isVisible() ) ) {
            await modeToggle.click( { timeout: 20000 } );
            await expect( expertButton ).toBeVisible( { timeout: 10000 } );
        }

        const isPressed = await expertButton.getAttribute( 'aria-pressed' );

        if ( isPressed !== 'true' ) {
            await expertButton.click( { timeout: 20000 } );
            await expect( expertButton ).toHaveAttribute( 'aria-pressed', 'true', {
                timeout: 10000,
            } );
        }

        return true;
    };

    const ensureExpertRadioSelected = async () => {
        const expertRadio = modeToggle.getByRole( 'radio', {
            name: 'Expert',
            exact: true,
        } );

        if ( ! ( await expertRadio.count() ) ) {
            return false;
        }

        if ( ! ( await expertRadio.isVisible() ) ) {
            await modeToggle.click( { timeout: 20000 } );
            await expect( expertRadio ).toBeVisible( { timeout: 10000 } );
        }

        if ( ! ( await expertRadio.isChecked() ) ) {
            await expertRadio.check( { timeout: 20000 } );
            await expect( expertRadio ).toBeChecked( { timeout: 10000 } );
        }

        return true;
    };

    for ( let attempt = 0; attempt < 3; attempt++ ) {
        if ( ( await ensureExpertButtonSelected() ) || ( await ensureExpertRadioSelected() ) ) {
            return;
        }

        await modeToggle.click( { timeout: 20000 } );
        await page.waitForTimeout( 500 );
    }

    throw new Error( 'Expert mode toggle not found in the inspector controls.' );
}
