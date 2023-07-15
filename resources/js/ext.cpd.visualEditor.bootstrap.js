var pluginModules = require( './pluginModules.json' );
mw.loader.using( 'ext.cpd.visualEditor' ).done( function () {
	mw.loader.using( pluginModules );
} );