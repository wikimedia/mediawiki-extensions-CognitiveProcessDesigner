window.ext = window.ext || {};

ext.cpd = ext.cpd || {};
ext.cpd.ve = ext.cpd.ve || {};
ext.cpd.ve.dm = ext.cpd.ve.dm || {};

ext.cpd.ve.dm.CPDProcessNode = function () {
	ext.cpd.ve.dm.CPDProcessNode.super.apply( this, arguments );
};

OO.inheritClass( ext.cpd.ve.dm.CPDProcessNode, ve.dm.MWExtensionNode );

ext.cpd.ve.dm.CPDProcessNode.static.name = 'bpmn';

ext.cpd.ve.dm.CPDProcessNode.static.tagName = 'div';

// Name of the parser tag
ext.cpd.ve.dm.CPDProcessNode.static.extensionName = 'bpmn';

// This tag renders without content
ext.cpd.ve.dm.CPDProcessNode.static.childNodeTypes = [];
ext.cpd.ve.dm.CPDProcessNode.static.isContent = false;

ve.dm.modelRegistry.register( ext.cpd.ve.dm.CPDProcessNode );
