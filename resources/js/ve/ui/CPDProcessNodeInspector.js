window.ext = window.ext || {};

ext.cpd = ext.cpd || {};
ext.cpd.ve = ext.cpd.ve || {};
ext.cpd.ve.ui = ext.cpd.ve.ui || {};

ext.cpd.ve.ui.CPDProcessNodeInspector = function ( config ) {
	// Parent constructor
	ext.cpd.ve.ui.CPDProcessNodeInspector.super.call(
		this, ve.extendObject( { padded: true }, config )
	);
};

/* Inheritance */

OO.inheritClass( ext.cpd.ve.ui.CPDProcessNodeInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */

ext.cpd.ve.ui.CPDProcessNodeInspector.static.name = 'cpdProcessInspector';

ext.cpd.ve.ui.CPDProcessNodeInspector.static.title = mw.message( 'cpd-droplet-name' ).plain();

ext.cpd.ve.ui.CPDProcessNodeInspector.static.modelClasses =
	[ ext.cpd.ve.dm.CPDProcessNode ];

ext.cpd.ve.ui.CPDProcessNodeInspector.static.dir = 'ltr';

// This tag does not have any content
ext.cpd.ve.ui.CPDProcessNodeInspector.static.allowedEmpty = true;
ext.cpd.ve.ui.CPDProcessNodeInspector.static.selfCloseEmptyBody = true;

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.initialize = function () {
	ext.cpd.ve.ui.CPDProcessNodeInspector.super.prototype.initialize.call( this );

	// remove input field with links in it
	this.input.$element.remove();

	this.indexLayout = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	this.createFields();

	this.setLayouts();

	// Initialization
	this.$content.addClass( 'cpd-process-inspector-body' );

	this.indexLayout.$element.append(
		this.processTitleLayout.$element,
		this.widthLayout.$element,
		this.heightLayout.$element,
		this.toolbarLayout.$element
	);
	this.form.$element.append(
		this.indexLayout.$element
	);
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.createFields = function () {
	this.processTitleInput = new ext.cpd.ui.ProcessInputWidget();
	this.width = new OO.ui.NumberInputWidget();
	this.height = new OO.ui.NumberInputWidget( {
		required: true
	} );
	this.toolbar = new OO.ui.ToggleSwitchWidget( {
		value: true
	} );
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.setLayouts = function () {
	this.processTitleLayout = new OO.ui.FieldLayout( this.processTitleInput, {
		align: 'left',
		label: ve.msg( 'cpd-droplet-process-field-label' ),
		help: ve.msg( 'cpd-droplet-process-field-label-help' )
	} );

	this.widthLayout = new OO.ui.FieldLayout( this.width, {
		align: 'left',
		label: ve.msg( 'cpd-droplet-width-field-label' ),
		help: ve.msg( 'cpd-droplet-width-field-label-help' )
	} );
	this.heightLayout = new OO.ui.FieldLayout( this.height, {
		align: 'left',
		label: ve.msg( 'cpd-droplet-height-field-label' ),
		help: ve.msg( 'cpd-droplet-height-field-label-help' )
	} );
	this.toolbarLayout = new OO.ui.FieldLayout( this.toolbar, {
		align: 'left',
		label: ve.msg( 'cpd-droplet-show-toolbar-field-label' ),
		help: ve.msg( 'cpd-droplet-show-toolbar-field-label-help' )
	} );
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.getSetupProcess = function ( data ) {
	return ext.cpd.ve.ui.CPDProcessNodeInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			const attributes = this.selectedNode.getAttribute( 'mw' ).attrs;

			this.processTitleInput.setValue( attributes.process || '' );
			this.width.setValue( attributes.width || '' );
			this.height.setValue( attributes.height || mw.config.get( 'cpdCanvasDefaultHeight' ) );

			if ( attributes.toolbar ) {
				this.toolbar.setValue( attributes.toolbar );
			}

			this.actions.setAbilities( { done: true } );
		}, this );
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.wireEvents = function () {
	this.processTitleInput.on( 'change', this.onChangeHandler );
	this.width.on( 'change', this.onChangeHandler );
	this.height.on( 'change', this.onChangeHandler );
	this.toolbar.on( 'change', this.onChangeHandler );
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.updateMwData = function ( mwData ) {
	ext.cpd.ve.ui.CPDProcessNodeInspector.super.prototype.updateMwData.call( this, mwData );

	if ( this.processTitleInput.getValue() !== '' ) {
		mwData.attrs.process = this.processTitleInput.getValue();
	}
	if ( this.width.getValue() !== '' ) {
		mwData.attrs.width = this.width.getValue();
	} else {
		delete ( mwData.attrs.width );
	}
	if ( this.height.getValue() !== '' ) {
		mwData.attrs.height = this.height.getValue();
	} else {
		delete ( mwData.attrs.height );
	}
	if ( this.toolbar.getValue() ) {
		mwData.attrs.toolbar = 'true';
	} else {
		delete ( mwData.attrs.toolbar );
	}
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.formatGeneratedContentsError = function ( $element ) {
	return $element.text().trim();
};

ext.cpd.ve.ui.CPDProcessNodeInspector.prototype.onTabPanelSet = function () {
	this.indexLayout.getCurrentTabPanel().$element.append( this.generatedContentsError.$element );
};

ve.ui.windowFactory.register( ext.cpd.ve.ui.CPDProcessNodeInspector );
