const mockMediaWiki = require( "@wikimedia/mw-node-qunit/src/mockMediaWiki.js" );
global.mw = mockMediaWiki();
global.mw.Title.newFromText = function ( text ) {
	return {
		getPrefixedDb: function () {
			return text;
		}
	};
};
global.mw.util.getUrl = function ( dbKey ) {
	return "/jest/index.php?title=" + dbKey;
};
global.mw.config = {
	get( key ) {
		switch ( key ) {
			case "cpdDedicatedSubpageTypes":
				return [ "task", "event" ];
			case "cpdProcessNamespace":
				return "Process";
			default:
				return null;
		}
	}
};
global.$ = require( "jquery" );
global.OO = require( "oojs" );
global.OO.ui = require( "oojs-ui" );
global.mw.Api.prototype.getUserInfo = function () {
	return "mocked user info";
};
global.document.getElementById = jest.fn( () => ( {
	textContent: "Mocked text content"
} ) );
