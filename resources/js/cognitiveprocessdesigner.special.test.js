window.onload = function() {
	mw.cpdManager.sandboxMode = true;
	mw.cpdManager.editBPMN(
		'test', $( '#cpd-wrapper'  ), $( '#cpd-img' ), $( '#cpd-btn-edit-bpmn-id' )
	);
};
