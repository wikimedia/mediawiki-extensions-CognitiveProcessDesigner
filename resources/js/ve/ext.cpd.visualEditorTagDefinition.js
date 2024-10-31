bs.util.registerNamespace( "bs.cpd.util.tag" );

bs.cpd.util.tag.BpmnDefinition = function BsVecUtilTagBpmnDefinition() {
	bs.cpd.util.tag.BpmnDefinition.super.call( this );
};

OO.inheritClass( bs.cpd.util.tag.BpmnDefinition, bs.vec.util.tag.Definition );

bs.cpd.util.tag.BpmnDefinition.prototype.getCfg = function () {
	var cfg = bs.cpd.util.tag.BpmnDefinition.super.prototype.getCfg.call( this );
	return $.extend( cfg, {
		classname: "BpmnDiagram",
		name: "bpmn",
		tagname: "bpmn",
		descriptionMsg: "cpd-droplet-description",
		menuItemMsg: "cpd-droplet-name",
		attributes: [ {
			name: "process",
			labelMsg: "cpd-droplet-process-field-label",
			helpMsg: "cpd-droplet-process-field-label-help",
			type: "custom",
			default: "",
			widgetClass: bs.cpd.ui.ProcessInputWidget
		} ]
	} );
};

bs.vec.registerTagDefinition( new bs.cpd.util.tag.BpmnDefinition() );
