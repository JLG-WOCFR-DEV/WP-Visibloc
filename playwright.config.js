const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

module.exports = {
    ...baseConfig,
    testMatch: [ '**/visi-bloc-jlg/tests/e2e/**/*.spec.js' ],
};
