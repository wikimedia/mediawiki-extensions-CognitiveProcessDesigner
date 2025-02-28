const {
	is
} = require( 'bpmnlint-utils' );


/**
 * Rule that reports gateways without labels.
 */
module.exports = function () {

	function check( node, reporter ) {
		if ( !is( node, 'bpmn:Gateway' ) ) {
			return;
		}

		const name = (node.name || '').trim();

		if (name.length === 0) {
			reporter.report(node.id, 'Element is missing label/name', [ 'name' ]);
		}
	}

	return { check };
};
