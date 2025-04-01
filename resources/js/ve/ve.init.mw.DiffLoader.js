/*!
 * VisualEditor MediaWiki DiffLoader.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* global ve */

/**
 * Diff loader.
 *
 * @class mw.libs.ve.diffLoader
 * @singleton
 * @hideconstructor
 */
( function () {
	mw.libs.ve = mw.libs.ve || {};
	const originalGetVisualDiffGeneratorPromise = mw.libs.ve.diffLoader.getVisualDiffGeneratorPromise;

	mw.libs.ve.diffLoader.getVisualDiffGeneratorPromise = function( oldId, newId, modulePromise, oldPageName, newPageName ) {
		return new Promise( ( resolve ) => {
			resolve( function() {
				console.log("Custom visualDiffGenerator executed!");
				return {
					render: function() {
						console.log("Rendering custom diff...");
					}
				};
			});
		});
	};

}() );
