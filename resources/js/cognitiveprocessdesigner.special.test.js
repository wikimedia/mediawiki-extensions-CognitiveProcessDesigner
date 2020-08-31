( function( $ ) {
	$( function() {
		mw.cpdManager.sandboxMode = true;
		mw.cpdManager.editBPMN(
			'test', $( '#cpd-wrapper'  ), $( '#cpd-img' ), $( '#cpd-btn-edit-bpmn-id' )
		);
	} );
} )( jQuery );
