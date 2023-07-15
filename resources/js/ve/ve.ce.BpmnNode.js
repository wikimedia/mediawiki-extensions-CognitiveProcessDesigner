ve.ce.BpmnNode = function VeCeBpmnNode() {
	// Parent constructor
	ve.ce.BpmnNode.super.apply( this, arguments );
};

/* Inheritance */
OO.inheritClass( ve.ce.BpmnNode, ve.ce.MWInlineExtensionNode );

/* Static properties */
ve.ce.BpmnNode.static.name = 'bpmn';
ve.ce.BpmnNode.static.primaryCommandName = 'bpmn';

// If body is empty, tag does not render anything
ve.ce.BpmnNode.static.rendersEmpty = false;

/* Registration */
ve.ce.nodeFactory.register( ve.ce.BpmnNode );
