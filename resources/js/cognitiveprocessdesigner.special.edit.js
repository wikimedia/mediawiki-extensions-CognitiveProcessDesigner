( function( $ ) {
	$( function() {
		var showPrompt = function() {
			OO.ui.prompt( mw.msg('cpd-enter-bpmn-id-placeholder') )
				.done( function ( bpmnName ) {
					mw.cpdManager.specialPageMode = false;
					mw.cpdManager.cancelCallback = showPrompt;
					mw.cpdManager.editBPMN(
						bpmnName, $( '#cpd-wrapper'  ), $( '#cpd-img' ), $( '#cpd-btn-edit-bpmn-id' )
					);
				});
		};
		showPrompt();
	} );
} )( jQuery );

