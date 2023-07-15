ve.ui.BpmnInspector = function VeUiBpmnInspector( config ) {
	// Parent constructor
	ve.ui.BpmnInspector.super.call( this, ve.extendObject( { padded: true }, config ) );
};

/* Inheritance */
OO.inheritClass( ve.ui.BpmnInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */
ve.ui.BpmnInspector.static.name = 'bpmnInspector';
ve.ui.BpmnInspector.static.title = mw.message( 'cpd-ve-bpmn-title' ).text();
ve.ui.BpmnInspector.static.modelClasses = [ ve.dm.BpmnNode ];
ve.ui.BpmnInspector.static.dir = 'ltr';

// This tag does not have any content
ve.ui.BpmnInspector.static.allowedEmpty = true;
ve.ui.BpmnInspector.static.selfCloseEmptyBody = false;

/**
 * @inheritdoc
 */
ve.ui.BpmnInspector.prototype.initialize = function () {
	ve.ui.BpmnInspector.super.prototype.initialize.call( this );

	var filenameProcessor = { processor: new cpd.FilenameProcessor() };
	mw.hook( 'cpd.makeFilenameProcessor' ).fire( filenameProcessor );
	this.filenameProcessor = filenameProcessor.processor;

	this.filename = this.filenameProcessor.initializeFilename();

	// remove input field with links in it
	this.input.$element.remove();

	this.createLayout();

	this.form.$element.append(
		this.indexLayout.$element
	);
};

ve.ui.BpmnInspector.prototype.createLayout = function ( ) {
	this.indexLayout = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	// InputWidget for diagram name
	this.nameInputWidget = new OO.ui.TextInputWidget( {
		validate: this.filenameProcessor.validateFilename
	} );
	this.nameInputWidget.on( 'change', this.onFileNameChange, [], this );
	this.nameInputLayout = new OO.ui.FieldLayout( this.nameInputWidget, {
		align: 'left',
		label: OO.ui.deferMsg( 'cpd-ve-bpmn-name-title' )
	} );

	// set default values
	this.nameInputWidget.setValue( this.filename );

	this.indexLayout.$element.append(
		this.nameInputLayout.$element
	);
};

ve.ui.BpmnInspector.prototype.onFileNameChange = function () {
	var actions = this.actions;
	actions.setAbilities( { done: false } );
	this.nameInputWidget.getValidity().done( function() {
		actions.setAbilities( { done: true } );
	} );
};

ve.ui.BpmnInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.BpmnInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.actions.setAbilities( { done: true } );
		}, this );
};

ve.ui.BpmnInspector.prototype.updateMwData = function ( mwData ) {
	ve.ui.BpmnInspector.super.prototype.updateMwData.call( this, mwData );

	var filename = this.nameInputWidget.getValue();
	// Get rid of the symbols which should not be in the diagram filename
	mwData.attrs.name = this.filenameProcessor.sanitizeFilename( filename );
};

/* Registration */
ve.ui.windowFactory.register( ve.ui.BpmnInspector );
