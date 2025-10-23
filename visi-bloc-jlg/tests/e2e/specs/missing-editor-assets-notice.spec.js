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

            const notice = page
                .locator( '.notice.notice-error' )
                .filter( { hasText: commandText } )
                .first();
            await expect( notice ).toBeVisible( { timeout: 20000 } );
            await expect( notice ).not.toHaveAttribute( 'hidden', /.*/, {
                timeout: 20000,
            } );
            await expect( notice ).not.toHaveAttribute( 'aria-hidden', 'true', {
                timeout: 20000,
            } );
            await expect( notice ).not.toHaveCSS( 'display', 'none' );
            await expect( notice ).not.toHaveCSS( 'visibility', 'hidden' );
        } finally {
            restoreAssetFile();
        }
    } );
} );
