const {
	is
} = require( 'bpmnlint-utils' );


/**
 * Rule that reports sub processes without labels.
 */
module.exports = function () {

	function check( node, reporter ) {
		if ( !is( node, 'bpmn:SubProcess' ) ) {
			return;
		}

		const name = (node.name || '').trim();

		if (name.length === 0) {
			reporter.report(node.id, 'Element is missing label/name', [ 'name' ]);
		}
	}

	return { check };
};
