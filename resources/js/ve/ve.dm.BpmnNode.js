ve.dm.BpmnNode = function BpmnNode() {
	// Parent constructor
	ve.dm.BpmnNode.super.apply( this, arguments );
};

/* Inheritance */
OO.inheritClass( ve.dm.BpmnNode, ve.dm.MWInlineExtensionNode );

/* Static members */
ve.dm.BpmnNode.static.name = 'bpmn';
ve.dm.BpmnNode.static.tagName = 'bpmn';

// Name of the parser tag
ve.dm.BpmnNode.static.extensionName = 'bpmn';

// This tag renders without content
ve.dm.BpmnNode.static.childNodeTypes = [];
ve.dm.BpmnNode.static.isContent = false;

/* Registration */
ve.dm.modelRegistry.register( ve.dm.BpmnNode );
