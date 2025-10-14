#!/usr/bin/env node
const { spawnSync } = require( 'child_process' );
const path = require( 'path' );

const shouldForceRun = [ '1', 'true', 'yes' ].includes( String( process.env.FORCE_E2E_TESTS || process.env.FORCE_E2E || '' ).toLowerCase() );

const dockerAvailable = () => {
    const result = spawnSync( 'docker', [ 'info' ], {
        stdio: 'ignore',
        windowsHide: true,
    } );

    if ( result.error ) {
        return false;
    }

    return result.status === 0;
};

if ( ! shouldForceRun && ! dockerAvailable() ) {
    console.warn( 'Skipping end-to-end tests because Docker is not available. Set FORCE_E2E_TESTS=1 to override.' );
    process.exit( 0 );
}

const isWindows = process.platform === 'win32';
const playwrightExecutable = path.resolve(
    __dirname,
    '../../node_modules/.bin',
    isWindows ? 'playwright.cmd' : 'playwright'
);

const configPath = path.resolve( __dirname, '../../playwright.config.js' );

const result = spawnSync( playwrightExecutable, [ 'test', `--config=${ configPath }` ], {
    stdio: 'inherit',
    windowsHide: true,
} );

if ( result.error ) {
    console.error( result.error.message );
    process.exit( result.status ?? 1 );
}

process.exit( result.status ?? 0 );
