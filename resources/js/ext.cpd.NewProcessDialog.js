window.ext.cpd = window.ext.cpd || {};

ext.cpd.NewProcessDialog = function NewProcessDialog( config ) {
	ext.cpd.NewProcessDialog.super.call( this, config );
	this.namespace = config.namespace;
	this.pageName = config.pageName;
	this.mainInput = null;
};
OO.inheritClass( ext.cpd.NewProcessDialog, OO.ui.ProcessDialog );

ext.cpd.NewProcessDialog.static.name = 'ext-cpd-new-process-dialog';
ext.cpd.NewProcessDialog.static.title = mw.message( 'bs-cpd-actionmenuentry-new-process' ).text();

ext.cpd.NewProcessDialog.prototype.makeSetupProcessData = function () {
	return {
		actions: [
			{
				action: 'done',
				label: mw.message( 'cpd-dialog-action-label-create' ).plain(),
				flags: [ 'primary', 'progressive' ],
				id: this.elementId + '-btn-done'
			},
			{
				title: mw.message( 'cpd-button-cancel-title' ).plain(),
				flags: [ 'safe', 'close' ],
				id: this.elementId + '-btn-cancel'
			}
		]
	};
};

ext.cpd.NewProcessDialog.prototype.getFormItems = function () {
	this.mainInput = new OOJSPlus.ui.widget.TitleInputWidget( {
		id: this.elementId + '-tf-target',
		$overlay: this.$overlay,
		mustExist: false,
		contentPagesOnly: false,
		namespaces: [ 1530 ]
	} );

	this.mainInput.connect( this, {
		change: 'onTitleChange'
	} );
	this.mainFieldset = new OO.ui.FieldLayout( this.mainInput, {
		label: mw.message( 'bs-cpd-actionmenuentry-new-process-input-label' ).plain(),
		align: 'top'
	} );

	return [ this.mainFieldset ];
};

ext.cpd.NewProcessDialog.prototype.makeDoneActionProcess = function () {
	this.newTitle = mw.Title.makeTitle( this.namespace, this.mainInput.getValue() );
	return new OO.ui.Process( ( () => {} ), this );
};

ext.cpd.NewProcessDialog.prototype.getDialogTitlePageName = function () {
	return this.pageName.replace( '_', ' ' );
};

ext.cpd.NewProcessDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	const additionalData = this.makeSetupProcessData();
	data = Object.assign( data, additionalData );
	return ext.cpd.NewProcessDialog.super.prototype.getSetupProcess.call( this, data );
};

ext.cpd.NewProcessDialog.prototype.initialize = function () {
	ext.cpd.NewProcessDialog.parent.prototype.initialize.call( this );

	this.content = new OO.ui.PanelLayout( {
		padded: true,
		expanded: true
	} );
	const formItems = this.getFormItems();
	this.content.$element.append(
		formItems.map( ( item ) => item.$element )
	);
	this.$body.append( this.content.$element );
};

ext.cpd.NewProcessDialog.prototype.getReadyProcess = function () {
	if ( this.mainInput.focus ) {
		this.mainInput.focus();
	}
	return new OO.ui.Process( () => {} );
};

ext.cpd.NewProcessDialog.prototype.show = function () {
	if ( !this.windowManager ) {
		this.windowManager = new OO.ui.WindowManager( {
			modal: true
		} );
		$( document.body ).append( this.windowManager.$element );
		this.windowManager.addWindows( [ this ] );
	}

	this.windowManager.openWindow( this );
};

ext.cpd.NewProcessDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) { // eslint-disable-line no-jquery/no-class-state
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}

	return this.$element.find( '.oo-ui-window-body' )[ 0 ].scrollHeight + 10;
};

ext.cpd.NewProcessDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'done' ) {
		const doneActionProcess = this.makeDoneActionProcess();
		doneActionProcess.next( this.onActionDone, this );
		return doneActionProcess;
	}
	return ext.cpd.NewProcessDialog.super.prototype.getActionProcess.call( this, action );
};

ext.cpd.NewProcessDialog.prototype.onActionDone = function ( action ) {
	let args = [ 'actioncompleted' ];
	args = args.concat( this.getActionCompletedEventArgs() );
	this.emit.apply( this, args );
	this.close( { action: action } );
};

ext.cpd.NewProcessDialog.prototype.getActionCompletedEventArgs = function () {
	return [ this.newTitle ];
};

ext.cpd.NewProcessDialog.prototype.onTitleChange = function ( value ) {
	if ( this.typeTimeout ) {
		clearTimeout( this.typeTimeout );
	}
	this.typeTimeout = setTimeout( () => {
		this.validateTitleNotExist( value );
	}, 500 );
};

ext.cpd.NewProcessDialog.prototype.validateTitleNotExist = function ( value ) {
	this.clearError();
	if ( !value ) {
		this.actions.setAbilities( { done: false } );
		return;
	}
	const newTitle = mw.Title.makeTitle( this.namespace, value );
	new mw.Api().get( {
		action: 'query',
		prop: 'pageprops',
		titles: newTitle.getPrefixedText()
	} ).done( ( data ) => {
		// Check if there is data.query.pages.-1
		if ( !data.query ) {
			this.actions.setAbilities( { done: false } );
			this.setError( mw.message( 'permissionserrors' ).text() );

			return;
		}

		if ( data.query && data.query.pages && data.query.pages[ -1 ] ) {
			// eslint-disable-next-line no-prototype-builtins
			if ( data.query.pages[ -1 ].hasOwnProperty( 'invalid' ) ) {
				this.actions.setAbilities( { done: false } );
				this.setError( data.query.pages[ -1 ].invalidreason );
			} else {
				this.actions.setAbilities( { done: true } );
			}
		} else {
			this.actions.setAbilities( { done: false } );
			this.setExistWarning();
		}
	} ).fail( () => {
		// Something went wrong, let user go to the page and deal with it there
		this.actions.setAbilities( { done: true } );
	} );
};

ext.cpd.NewProcessDialog.prototype.setError = function ( error ) {
	this.mainFieldset.setErrors( [ error ] );
	if ( this.mainInput.lookupMenu ) {
		this.mainInput.lookupMenu.toggle( false );
	}
	this.updateSize();
};

ext.cpd.NewProcessDialog.prototype.clearError = function () {
	this.mainFieldset.setWarnings( [] );
	this.mainFieldset.setErrors( [] );
	this.updateSize();
};

ext.cpd.NewProcessDialog.prototype.setExistWarning = function () {
	if ( this.mainFieldset ) {
		this.mainFieldset.setWarnings( [ mw.message( 'cpd-dialog-validation-page-exist-info-label' ).text() ] );
	}
	this.updateSize();
};

$( document ).on( 'click', '#ca-cpd-create-process, #ca-cpd-create-new-process, .cpd-create-new-process', ( e ) => {
	const diag = new ext.cpd.NewProcessDialog( {
		namespace: 1530
	} );
	diag.on( 'actioncompleted', ( newTitle ) => {
		window.location.href = newTitle.getUrl( { action: 'edit' } );
	} );
	diag.show();

	e.defaultPrevented = true;
	return false;
} );
