window.ext.cpd = window.ext.cpd || {};

ext.cpd.NewProcessDialog = function NewProcessDialog( config ) {
	ext.cpd.NewProcessDialog.super.call( this, config );
	this.namespace = config.namespace;
};
OO.inheritClass( ext.cpd.NewProcessDialog, StandardDialogs.ui.NewPageDialog );

ext.cpd.NewProcessDialog.prototype.makeSetupProcessData = function () {
	const data = ext.cpd.NewProcessDialog.super.prototype.makeSetupProcessData.call( this );
	data.title = mw.message( 'bs-cpd-actionmenuentry-new-process' ).plain();

	return data;
};

ext.cpd.NewProcessDialog.prototype.getFormItems = function () {
	this.titleInputWidget = new OOJSPlus.ui.widget.TitleInputWidget( {
		id: this.elementId + '-tf-target',
		$overlay: this.$overlay,
		mustExist: false,
		contentPagesOnly: false,
		namespaces: [ 1530 ]
	} );

	return [
		new OO.ui.FieldsetLayout( {
			items: [
				new OO.ui.FieldLayout( this.titleInputWidget, {
					label: mw.message( 'bs-cpd-actionmenuentry-new-process-input-label' ).plain(),
					align: 'top'
				} )
			]
		} )
	];
};

ext.cpd.NewProcessDialog.prototype.makeDoneActionProcess = function () {
	this.newTitle = mw.Title.newFromText( this.titleInputWidget.getValue(), this.namespace );
	return new OO.ui.Process( ( () => {} ), this );
};

$( document ).on( 'click', '#ca-cpd-create-process, #ca-cpd-create-new-process', ( e ) => {
	const diag = new ext.cpd.NewProcessDialog( {
		proc: 'standarddialogs-dlg-new-page',
		namespace: 1530
	} );
	diag.on( 'actioncompleted', ( newTitle ) => {
		window.location.href = newTitle.getUrl( { action: 'edit' } );
	} );
	diag.show();

	e.defaultPrevented = true;
	return false;
} );
