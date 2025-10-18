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

    const isExpanded = await panelToggle.getAttribute( 'aria-expanded' );

    if ( isExpanded === 'true' ) {
        return;
    }

    await panelToggle.click( { timeout: 20000 } );
}

export async function ensureExpertMode( page, editor ) {
    await ensureVisiblocPanelOpen( page );

    const modeToggle = await canvasAwareLocator( page, editor, '.visibloc-editor-mode__toggle' );

    await expect( modeToggle ).toBeVisible( { timeout: 20000 } );

    const expertButton = modeToggle.getByRole( 'button', {
        name: 'Expert',
        exact: true,
    } );

    if ( await expertButton.count() ) {
        const isPressed = await expertButton.getAttribute( 'aria-pressed' );

        if ( isPressed !== 'true' ) {
            await expertButton.click( { timeout: 20000 } );
        }

        return;
    }

    const expertRadio = modeToggle.getByRole( 'radio', {
        name: 'Expert',
        exact: true,
    } );

    if ( await expertRadio.count() ) {
        if ( ! ( await expertRadio.isChecked() ) ) {
            await expertRadio.check( { timeout: 20000 } );
        }

        return;
    }

    throw new Error( 'Expert mode toggle not found in the inspector controls.' );
}
