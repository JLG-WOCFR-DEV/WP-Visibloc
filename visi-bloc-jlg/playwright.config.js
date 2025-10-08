const path = require( 'path' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

module.exports = {
    ...baseConfig,
    testDir: path.resolve( __dirname, 'tests/e2e' ),
    testMatch: [ '**/*.spec.js' ],
};
