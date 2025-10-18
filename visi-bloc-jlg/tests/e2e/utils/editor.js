import { expect } from '@wordpress/e2e-test-utils-playwright';

function hasCanvas( editor ) {
    return Boolean( editor && editor.page && editor.canvas );
}

export function canvasAwareLocator( page, editor, selector, options ) {
    const baseLocator = page.locator( selector, options );

    if ( hasCanvas( editor ) ) {
        const frameLocator = editor.canvas.locator( selector, options );
        return baseLocator.or( frameLocator );
    }

    return baseLocator;
}

export function canvasAwareGetByRole( page, editor, role, options ) {
    const baseLocator = page.getByRole( role, options );

    if ( hasCanvas( editor ) && typeof editor.canvas.getByRole === 'function' ) {
        const frameLocator = editor.canvas.getByRole( role, options );
        return baseLocator.or( frameLocator );
    }

    return baseLocator;
}

export function canvasAwareGetByLabel( page, editor, text, options ) {
    const baseLocator = page.getByLabel( text, options );

    if ( hasCanvas( editor ) && typeof editor.canvas.getByLabel === 'function' ) {
        const frameLocator = editor.canvas.getByLabel( text, options );
        return baseLocator.or( frameLocator );
    }

    return baseLocator;
}

export function canvasAwareGetByText( page, editor, text, options ) {
    const baseLocator = page.getByText( text, options );

    if ( hasCanvas( editor ) && typeof editor.canvas.getByText === 'function' ) {
        const frameLocator = editor.canvas.getByText( text, options );
        return baseLocator.or( frameLocator );
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

    const modeToggle = canvasAwareLocator( page, editor, '.visibloc-editor-mode__toggle' );

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
