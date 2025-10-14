const path = require( 'path' );
const { spawnSync } = require( 'child_process' );

const baseGlobalSetup = require( '@wordpress/scripts/config/playwright/global-setup.js' );

const PLUGIN_ROOT = path.resolve( __dirname, '..', '..' );
const WP_ENV_BINARY = path.resolve(
    PLUGIN_ROOT,
    'node_modules',
    '.bin',
    process.platform === 'win32' ? 'wp-env.cmd' : 'wp-env'
);
const WP_ENV_SHELL = process.platform === 'win32';

function sleep( ms ) {
    return new Promise( ( resolve ) => setTimeout( resolve, ms ) );
}

function runWpEnvCommand( service, args ) {
    const result = spawnSync(
        WP_ENV_BINARY,
        [ 'run', service, ...args ],
        {
            cwd: PLUGIN_ROOT,
            env: process.env,
            encoding: 'utf-8',
            stdio: 'pipe',
            shell: WP_ENV_SHELL,
            timeout: Number( process.env.WP_INSTALL_COMMAND_TIMEOUT || 60000 ),
        }
    );

    return {
        error: result.error || null,
        status: result.status ?? 1,
        stdout: result.stdout || '',
        stderr: result.stderr || '',
    };
}

async function ensureEnvironmentInstalled( service, url ) {
    const maxAttempts = Number( process.env.WP_INSTALL_MAX_ATTEMPTS || 6 );
    const delayMs = Number( process.env.WP_INSTALL_RETRY_DELAY_MS || 5000 );
    let lastErrorMessage = '';

    for ( let attempt = 1; attempt <= maxAttempts; attempt++ ) {
        const check = runWpEnvCommand( service, [ 'wp', 'core', 'is-installed' ] );

        if ( check.error ) {
            lastErrorMessage = check.error.message;
        } else if ( check.status === 0 ) {
            return;
        } else {
            const install = runWpEnvCommand( service, [
                'wp',
                'core',
                'install',
                `--url=${ url }`,
                '--title=Visi-Bloc local',
                '--admin_user=admin',
                '--admin_password=password',
                '--admin_email=admin@example.com',
                '--skip-email',
            ] );

            if ( install.error ) {
                lastErrorMessage = install.error.message;
            } else if ( install.status === 0 ) {
                return;
            } else {
                lastErrorMessage = install.stderr || install.stdout || 'Unknown error';
            }
        }

        if ( attempt < maxAttempts ) {
            await sleep( delayMs );
        }
    }

    throw new Error(
        `Unable to install WordPress for ${ service } after ${ maxAttempts } attempts: ${ lastErrorMessage }`
    );
}

async function ensureWordPressInstalled() {
    const testsBaseURL = process.env.WP_BASE_URL || 'http://localhost:8889';
    const developmentBaseURL = process.env.WP_DEV_BASE_URL || 'http://localhost:8888';

    await ensureEnvironmentInstalled( 'tests-cli', testsBaseURL );
    await ensureEnvironmentInstalled( 'cli', developmentBaseURL );
}

module.exports = async ( config ) => {
    await ensureWordPressInstalled();
    return baseGlobalSetup( config );
};
