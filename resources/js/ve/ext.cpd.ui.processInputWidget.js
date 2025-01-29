bs.util.registerNamespace( 'bs.cpd.ui' );

bs.cpd.ui.ProcessInputWidget = function ( cfg ) {
	cfg.namespace = 1530;
	cfg.icon = 'search';
	cfg.required = true;
	cfg.$overlay = true;
	bs.cpd.ui.ProcessInputWidget.super.call( this, cfg );
};

OO.inheritClass( bs.cpd.ui.ProcessInputWidget, mw.widgets.TitleInputWidget );

/**
 * @inheritdoc OO.ui.mixin.LookupElement
 */
bs.cpd.ui.ProcessInputWidget.prototype.getLookupMenuOptionsFromData = function ( response ) {

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
