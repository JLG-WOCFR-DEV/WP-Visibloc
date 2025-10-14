const path = require( 'path' );
const { execSync } = require( 'child_process' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

let canStartWordPressEnv = true;

try {
    execSync( 'docker info', { stdio: 'ignore' } );
} catch ( error ) {
    canStartWordPressEnv = false;
    // eslint-disable-next-line no-console -- Provide a helpful notice when the tests are skipped.
    console.warn(
        'Docker is not available. Skipping WordPress-dependent end-to-end tests.',
    );
}

process.env.VISIBLOC_CAN_START_WP = canStartWordPressEnv ? 'true' : 'false';

const config = {
    ...baseConfig,
    testDir: path.resolve( __dirname, 'tests/e2e' ),
    testMatch: [ '**/*.spec.js' ],
};

if ( ! canStartWordPressEnv ) {
    config.globalSetup = undefined;
    config.webServer = undefined;
}

module.exports = config;
