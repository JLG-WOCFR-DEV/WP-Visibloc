import fs from 'fs';
import path from 'path';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const PLUGIN_SLUG = 'visi-bloc-jlg';
const PLUGIN_ROOT = path.resolve( __dirname, '../../..' );
const ASSET_FILE = path.join( PLUGIN_ROOT, 'build/index.asset.php' );
const BACKUP_FILE = `${ ASSET_FILE }.bak-e2e`;

function restoreAssetFile() {
    if ( fs.existsSync( BACKUP_FILE ) ) {
        fs.renameSync( BACKUP_FILE, ASSET_FILE );
    }
}

test.describe( 'Visi-Bloc missing editor assets notice', () => {
    test.beforeEach( async ( { requestUtils } ) => {
        restoreAssetFile();
        await requestUtils.activatePlugin( PLUGIN_SLUG );
    } );

    test.afterEach( async ( { requestUtils } ) => {
        restoreAssetFile();
        await requestUtils.deactivatePlugin( PLUGIN_SLUG );
    } );

    test( 'displays an admin notice with the build command when assets are missing', async ( { admin, page } ) => {
        if ( ! fs.existsSync( ASSET_FILE ) ) {
            test.skip();
        }

        fs.renameSync( ASSET_FILE, BACKUP_FILE );

        try {
            await admin.visitAdminPage( 'post-new.php' );
            await page.waitForLoadState( 'networkidle' );

            const commandText = 'npm install && npm run build';

            await page.waitForFunction( ( text ) => {
                var notices = document.querySelectorAll( '.notice.notice-error' );

                return Array.prototype.some.call( notices, function( notice ) {
                    if ( ! notice || ! notice.textContent || notice.textContent.indexOf( text ) === -1 ) {
                        return false;
                    }

                    var computedStyle = window.getComputedStyle( notice );

                    return (
                        ! notice.hidden &&
                        notice.getAttribute( 'hidden' ) === null &&
                        notice.getAttribute( 'aria-hidden' ) !== 'true' &&
                        computedStyle.display !== 'none' &&
                        computedStyle.visibility !== 'hidden' &&
                        notice.style.display === '' &&
                        notice.style.visibility !== 'hidden'
                    );
                } );
            }, commandText );

            const notice = page.locator( '.notice.notice-error' ).filter( { hasText: commandText } ).first();
            await notice.waitFor( { state: 'visible', timeout: 20000 } );
            await expect( notice ).toBeVisible( { timeout: 20000 } );
            await expect.poll( async () => notice.getAttribute( 'hidden' ) ).toBeNull();
            await expect.poll( async () => notice.getAttribute( 'aria-hidden' ) ).not.toBe( 'true' );
            await expect( notice ).not.toHaveCSS( 'display', 'none' );
            await expect( notice ).not.toHaveCSS( 'visibility', 'hidden' );
        } finally {
            restoreAssetFile();
        }
    } );
} );
