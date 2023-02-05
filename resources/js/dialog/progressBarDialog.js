window.ext = window.ext || {};
ext.cpd = ext.cpd || {};

ext.cpd.ProgressBarDialog = function( config ) {
    ext.cpd.ProgressBarDialog.super.call( this, config );
};
OO.inheritClass( ext.cpd.ProgressBarDialog, OO.ui.Dialog );

ext.cpd.ProgressBarDialog.static.name = 'cpd-diagram-save-dialog';
ext.cpd.ProgressBarDialog.static.title = mw.message( 'cpd-diagram-save-dialog-title' ).text();

ext.cpd.ProgressBarDialog.prototype.setup = function( data ) {
	this.content = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );

	var progressBar = new OO.ui.ProgressBarWidget();

	var elementsAmount = data.elementsAmount;

	var progressField = new OO.ui.FieldLayout( progressBar, {
		label: mw.message( 'cpd-diagram-save-dialog-span', elementsAmount ).text(),
		align: 'top'
	} );

	this.content.$element.append( progressField.$element );

	this.$body.append( this.content.$element );

	return ext.cpd.ProgressBarDialog.super.prototype.setup.call( this, data );
};

ext.cpd.ProgressBarDialog.prototype.getBodyHeight = function () {
	return this.content.$element.outerHeight( true );
};

ext.cpd.ProgressBarDialog.prototype.getTeardownProcess = function ( data ) {
	return ext.cpd.ProgressBarDialog.super.prototype.getTeardownProcess.call( this, data ).first( function() {
		this.content.$element.remove();
	}, this );
}
