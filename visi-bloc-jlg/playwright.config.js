const path = require( 'path' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );
const globalSetup = require.resolve( './tests/e2e/global-setup.js' );

module.exports = {
    ...baseConfig,
    testDir: path.resolve( __dirname, 'tests/e2e' ),
    testMatch: [ '**/*.spec.js' ],
    globalSetup,
};
