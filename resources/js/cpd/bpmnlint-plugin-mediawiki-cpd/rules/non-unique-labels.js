const {
	isAny
} = require( 'bpmnlint-utils' );


/**
 * Rule that reports labels that are not unique.
 *
 * Attention: run npm install and commit changed bpmn-lint.config.js after changing this file.
 */
module.exports = function () {

	function check( node, reporter ) {
		if ( !isAny( node, ['bpmn:Process', 'bpmn:SubProcess'] ) ) {
			return;
		}

		const allElements = node.flowElements || [];
		const labels = allElements.filter( element => element.name );
		const uniqueLabels = new Set();
		const duplicateLabels = [];
		labels.forEach( label => {
			if ( uniqueLabels.has( label.name ) ) {
				duplicateLabels.push( label );
			} else {
				uniqueLabels.add( label.name );
			}
		} );
		duplicateLabels.forEach( label => {
			reporter.report( label.id, 'Label is not unique', ['name'] );
		} );
	}

	return { check };
};
