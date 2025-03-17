window.ext = window.ext || {};

ext.cpd = ext.cpd || {};
ext.cpd.ve = ext.cpd.ve || {};
ext.cpd.ve.ce = ext.cpd.ve.ce || {};

ext.cpd.ve.ce.CPDProcessNode = function () {
	ext.cpd.ve.ce.CPDProcessNode.super.apply( this, arguments );
};

OO.inheritClass( ext.cpd.ve.ce.CPDProcessNode, ve.ce.MWExtensionNode );

ext.cpd.ve.ce.CPDProcessNode.static.name = 'bpmn';

ext.cpd.ve.ce.CPDProcessNode.static.primaryCommandName = 'bpmnCommand';

// If body is empty, tag does not render anything
ext.cpd.ve.ce.CPDProcessNode.static.rendersEmpty = false;

/* Registration */
ve.ce.nodeFactory.register( ext.cpd.ve.ce.CPDProcessNode );
