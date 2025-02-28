const {
	is
} = require( 'bpmnlint-utils' );


/**
 * Rule that reports labels that are not unique between participants.
 */
module.exports = function () {

	function check( node, reporter ) {
		if ( !is( node, 'bpmn:Collaboration' ) ) {
			return;
		}

		const allParticipants = node.participants || [];
		const labels = allParticipants.filter(element => element.name);
		const uniqueLabels = new Set();
		const duplicateLabels = [];
		labels.forEach(label => {
			if (uniqueLabels.has(label.name)) {
				duplicateLabels.push(label);
			} else {
				uniqueLabels.add(label.name);
			}
		});
		duplicateLabels.forEach(label => {
			reporter.report(label.id, 'Label is not unique', [ 'name' ]);
		});
	}

	return { check };
};
