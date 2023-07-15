ve.ui.BpmnNodeTool = function BpmnNodeTool( toolGroup, config ) {
	ve.ui.BpmnNodeTool.super.call( this, toolGroup, config );
};

OO.inheritClass( ve.ui.BpmnNodeTool, ve.ui.FragmentInspectorTool );

ve.ui.BpmnNodeTool.static.name = 'bpmnTool';
ve.ui.BpmnNodeTool.static.group = 'none';
ve.ui.BpmnNodeTool.static.autoAddToCatchall = false;
ve.ui.BpmnNodeTool.static.modelClasses = [ ve.dm.BpmnNode ];
ve.ui.BpmnNodeTool.static.commandName = 'bpmnCommand';

ve.ui.toolFactory.register( ve.ui.BpmnNodeTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'bpmnCommand', 'window', 'open',
		{ args: [ 'bpmnInspector' ], supportedSelections: [ 'linear' ] }
	)
);
