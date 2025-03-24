window.ext = window.ext || {};

ext.cpd = ext.cpd || {};
ext.cpd.ve = ext.cpd.ve || {};
ext.cpd.ve.ui = ext.cpd.ve.ui || {};

ext.cpd.ve.ui.CPDProcessNodeInspectorTool = function ( config ) {
	// Parent constructor
	ext.cpd.ve.ui.CPDProcessNodeInspectorTool.super.call(
		this, ve.extendObject( { padded: true }, config )
	);
};

/* Inheritance */

OO.inheritClass( ext.cpd.ve.ui.CPDProcessNodeInspectorTool, ve.ui.FragmentInspectorTool );

/* Static properties */

ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.name = 'cpdProcessInspectorTool';
ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.group = 'none';
ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.title = mw.message( 'cpd-droplet-name' ).plain();

ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.autoAddToCatchall = false;
ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.autoAddToGroup = false;

ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.modelClasses = [ ext.cpd.ve.dm.CPDProcessNode ];
ext.cpd.ve.ui.CPDProcessNodeInspectorTool.static.commandName = 'bpmnCommand';

ve.ui.toolFactory.register( ext.cpd.ve.ui.CPDProcessNodeInspectorTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'bpmnCommand', 'window', 'open',
		{ args: [ 'cpdProcessInspector' ], supportedSelections: [ 'linear' ] }
	)
);
