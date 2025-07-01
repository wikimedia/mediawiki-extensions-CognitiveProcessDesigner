ve.ui.DiffElement = function VeUiDiffElement( visualDiff, config ) {
	const module = require( 'ext.cpd.cpddiffer' );
	const bpmnDiffer = new module.CpdBpmnDiffer(
		mw.config.get( "cpdProcess" ),
		mw.config.get( "cpdDiffContainerHeight" )
	);

	ve.ui.DiffElement.super.call( this, config );
	this.$document = $( '<div>' ).addClass( 've-ui-diffElement-document' );

	bpmnDiffer.createDiff( mw.config.get( "wgDiffOldId" ), mw.config.get( "wgDiffNewId" ) ).then(
		( diff ) => this.$element.append( $( diff ) )
	);
};
OO.inheritClass( ve.ui.DiffElement, OO.ui.Element );
