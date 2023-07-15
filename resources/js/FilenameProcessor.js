window.cpd = window.cpd || {};

cpd.FilenameProcessor = function() {};

OO.initClass( cpd.FilenameProcessor );

/**
 * Suggests initial filename for DrawIO diagram
 *
 * @returns {string}
 */
cpd.FilenameProcessor.prototype.initializeFilename = function() {
	var filename = mw.config.get( 'wgTitle' ) + '-' + ( Math.floor( Math.random() * 100000000) + 1 );
	// filename must only contain alphanumeric characters, dashes and underscores
	filename = this.sanitizeFilename( filename );

	return filename;
};

/**
 * Fired after each filename change.
 * Checks if string could be a valid filename
 *
 * @param {string} filename
 * @returns {boolean}
 */
cpd.FilenameProcessor.prototype.validateFilename = function( filename ) {
	if ( filename === '' ) {
		return false;
	}
	if ( !filename.match( /^[\w,-.\s]+$/ ) ) {
		return false;
	}
	return true;
};

/**
 * Sanitizes specified string to be a valid filename.
 *
 * @param {string} filename
 * @returns {string}
 */
cpd.FilenameProcessor.prototype.sanitizeFilename = function( filename ) {
	// filename must only contain alphanumeric characters, and underscores
	filename = filename.replace( /[^a-zA-Z0-9_-]/g, '_' );

	return filename;
};