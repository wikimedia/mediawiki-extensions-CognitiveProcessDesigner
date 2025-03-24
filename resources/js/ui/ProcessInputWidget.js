window.ext = window.ext || {};

ext.cpd = ext.cpd || {};
ext.cpd.ui = ext.cpd.ui || {};

ext.cpd.ui.ProcessInputWidget = function ( cfg ) {
	cfg = cfg || {};
	cfg.namespace = 1530;
	cfg.icon = 'search';
	cfg.required = true;
	cfg.$overlay = true;
	ext.cpd.ui.ProcessInputWidget.super.call( this, cfg );
};

OO.inheritClass( ext.cpd.ui.ProcessInputWidget, mw.widgets.TitleInputWidget );

/**
 * @inheritdoc OO.ui.mixin.LookupElement
 */
ext.cpd.ui.ProcessInputWidget.prototype.getLookupMenuOptionsFromData = function ( response ) {

	// Filter pages without contentmodel cpd from the response
	const filteredPages = {};

	for ( const key in response.pages ) {
		if ( response.pages[ key ].contentmodel === 'CPD' ) {
			filteredPages[ key ] = response.pages[ key ];
		}
	}

	response.pages = filteredPages;

	return this.getOptionsFromData( response );
};
