bs.util.registerNamespace( "bs.cpd.util.tag" );

bs.cpd.util.tag.BpmnDefinition = function BsVecUtilTagBpmnDefinition() {
	bs.cpd.util.tag.BpmnDefinition.super.call( this );
};

OO.inheritClass( bs.cpd.util.tag.BpmnDefinition, bs.vec.util.tag.Definition );

bs.cpd.util.tag.BpmnDefinition.prototype.getCfg = function () {
	const cfg = bs.cpd.util.tag.BpmnDefinition.super.prototype.getCfg.call( this );
	const defaultHeight = mw.config.get( 'cpdCanvasDefaultHeight' );

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
		}, {
			name: "width",
			labelMsg: "cpd-droplet-width-field-label",
			helpMsg: "cpd-droplet-width-field-label-help",
			type: "number",
			required: false
		}, {
			name: "height",
			labelMsg: "cpd-droplet-height-field-label",
			helpMsg: "cpd-droplet-height-field-label-help",
			type: "number",
			default: defaultHeight,
			required: true
		}, {
			name: "toolbar",
			labelMsg: "cpd-droplet-show-toolbar-field-label",
			helpMsg: "cpd-droplet-show-toolbar-field-label-help",
			type: "toggle",
			default: true,
			required: true
		} ]
	} );
};

bs.vec.registerTagDefinition( new bs.cpd.util.tag.BpmnDefinition() );
