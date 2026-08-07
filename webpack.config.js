/**
 * Extends the @wordpress/scripts default webpack config to add the wp-admin
 * publisher dashboard as an entry point, alongside the auto-detected block
 * scripts (the build still runs with `--webpack-src-dir=blocks`).
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const baseEntry =
	typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: defaultConfig.entry;

/**
 * Normalizes `devServer.proxy` to the webpack-dev-server 5 array form.
 *
 * The wds 4.x line is unpatched for a run of source-disclosure/CSRF advisories,
 * so `package.json` pins wds 5 via `overrides`. wds 5 dropped the object form of
 * `devServer.proxy`, which the @wordpress/scripts default config still emits, and
 * rejects it at startup — so `npm run start -- --hot` needs the array shape.
 * A no-op once wp-scripts emits an array itself.
 *
 * @param {Object} devServer The default config's devServer block (may be undefined in production).
 * @return {Object} The devServer block with an array-form proxy.
 */
const withArrayProxy = ( devServer ) => {
	if ( ! devServer || ! devServer.proxy || Array.isArray( devServer.proxy ) ) {
		return devServer;
	}

	return {
		...devServer,
		proxy: Object.entries( devServer.proxy ).map(
			( [ context, options ] ) => ( {
				context: [ context ],
				target: `http://${ devServer.host || 'localhost' }:${
					devServer.port || 8887
				}`,
				...options,
			} )
		),
	};
};

module.exports = {
	...defaultConfig,
	devServer: withArrayProxy( defaultConfig.devServer ),
	entry: {
		...baseEntry,
		'admin/index': path.resolve( process.cwd(), 'blocks', 'admin', 'index.js' ),
		'post-panel/index': path.resolve(
			process.cwd(),
			'blocks',
			'post-panel',
			'index.js'
		),
		'event-panel/index': path.resolve(
			process.cwd(),
			'blocks',
			'event-panel',
			'index.js'
		),
	},
};
