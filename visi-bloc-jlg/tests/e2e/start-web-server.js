const path = require( 'path' );
const { spawn } = require( 'child_process' );
const { URL } = require( 'url' );
const http = require( 'http' );
const https = require( 'https' );

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

function ping( targetUrl ) {
    const urlObj = new URL( targetUrl );
    const client = urlObj.protocol === 'https:' ? https : http;

    return new Promise( ( resolve, reject ) => {
        const request = client.request(
            {
                hostname: urlObj.hostname,
                port: urlObj.port,
                path: urlObj.pathname + urlObj.search,
                method: 'GET',
                rejectUnauthorized: false,
            },
            ( response ) => {
                // Consume the response stream to avoid socket hang ups.
                response.resume();
                resolve( response.statusCode || 0 );
            }
        );

        request.on( 'error', reject );
        request.end();
    } );
}

async function waitForServerReady( url, timeoutMs ) {
    const startTime = Date.now();
    let lastError;

    while ( Date.now() - startTime < timeoutMs ) {
        try {
            const status = await ping( url );

            if ( status >= 200 && status < 400 ) {
                return;
            }

            lastError = new Error( `Received status code ${ status }` );
        } catch ( error ) {
            lastError = error;
        }

        await sleep( 2000 );
    }

    const errorMessage = lastError ? lastError.message : 'Unknown error';
    throw new Error( `Timed out waiting for ${ url }: ${ errorMessage }` );
}

async function main() {
    const wpEnv = spawn( WP_ENV_BINARY, [ 'start' ], {
        cwd: PLUGIN_ROOT,
        env: process.env,
        stdio: 'inherit',
        shell: WP_ENV_SHELL,
    } );

    wpEnv.on( 'error', ( error ) => {
        // eslint-disable-next-line no-console
        console.error( error.message );
        process.exit( 1 );
    } );

    let readinessResolved = false;
    let pendingExit = { code: null, signal: null };

    wpEnv.on( 'exit', ( code, signal ) => {
        pendingExit = { code, signal };

        if ( signal ) {
            process.kill( process.pid, signal );
            return;
        }

        if ( readinessResolved ) {
            process.exit( code ?? 0 );
            return;
        }

        if ( typeof code === 'number' && code !== 0 ) {
            process.exit( code );
        }
    } );

    const shutdown = () => {
        if ( ! wpEnv.killed ) {
            wpEnv.kill();
        }
    };

    process.on( 'SIGINT', shutdown );
    process.on( 'SIGTERM', shutdown );

    const baseUrl = process.env.WP_BASE_URL || 'http://localhost:8889';
    const timeout = Number( process.env.WP_ENV_START_TIMEOUT || 180_000 );

    try {
        await waitForServerReady( baseUrl, timeout );
        readinessResolved = true;

        if ( pendingExit.signal ) {
            process.kill( process.pid, pendingExit.signal );
            return;
        }

        if ( typeof pendingExit.code === 'number' ) {
            process.exit( pendingExit.code );
        }

        // eslint-disable-next-line no-console
        console.log( `WordPress environment available at ${ baseUrl }` );
    } catch ( error ) {
        // eslint-disable-next-line no-console
        console.error( error.message );
        shutdown();
        process.exit( 1 );
    }
}

main().catch( ( error ) => {
    // eslint-disable-next-line no-console
    console.error( error.message );
    process.exit( 1 );
} );
