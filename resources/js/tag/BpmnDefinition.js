bs.util.registerNamespace( 'cpd.tag' );

cpd.tag.BpmnDefinition = function CpdVecTagDefinition() {
	cpd.tag.BpmnDefinition.super.call( this );
};

OO.inheritClass( cpd.tag.BpmnDefinition, bs.vec.util.tag.Definition );

cpd.tag.BpmnDefinition.prototype.getCfg = function() {
	var cfg = cpd.tag.BpmnDefinition.super.prototype.getCfg.call( this );
	return $.extend( cfg, {
		classname: "Bpmn",
		name: "bpmn",
		tagname: "bpmn",
		menuItemMsg: "cpd-ve-bpmn-title",
		descriptionMsg: "cpd-ve-bpmn-desc",
		attributes: [
			{
				name: 'name',
				labelMsg: 'cpd-ve-bpmn-name-title',
				helpMsg: 'cpd-ve-bpmn-name-help',
				type: 'text',
				default: '',
				placeholderMsg: 'cpd-ve-bpmn-name-placeholder'
			}
		]
	} );
};

cpd.tag.BpmnDefinition.prototype.getNewElement = function() {
	return {
		type: 'bpmn',
		attributes: {
			mw: {
				name: 'bpmn',
				attrs: { name: mw.config.get( 'wgTitle' ) + "-" + ( Math.floor( Math.random() * 100000000 ) + 1 ) },
				body: {
					extsrc: ''
				}
			}
		}
	};
};

bs.vec.registerTagDefinition(
	new cpd.tag.BpmnDefinition()
);
