import ElementRegistry from "diagram-js/lib/core/ElementRegistry";
import CpdElement from "../model/CpdElement";

export default class CpdInlineSvgRenderer {

	private elementRegistry: ElementRegistry;

	private largeTypes: string[] = ["bpmn:Participant", "bpmn:SubProcess"];

	public constructor( elementRegistry: ElementRegistry ) {
		this.elementRegistry = elementRegistry;
	}

	public getSVGFromElement( element: CpdElement ): string {
		const svgElement = this.elementRegistry.getGraphics( element.bpmnElement );

		if ( !svgElement ) {
			return element.id;
		}

		const svgElements = svgElement.getElementsByClassName( "djs-visual" )[0];
		let width = 0;
		let height = 0;
		let result = "";
		for ( let i = 0; i < svgElements.children.length; i++ ) {
			let element = svgElements.children[i];
			const boundingClientRect = element.getBoundingClientRect();
			width = Math.max( width, boundingClientRect.width );
			height = Math.max( height, boundingClientRect.height );
			const clone = element.cloneNode( true ) as SVGElement;
			result += clone.outerHTML;
		}

		// Add spacing
		width += 15;
		height += 15;

		const type = element.type;
		const largeCls = this.largeTypes.includes(type) ? "large" : "";

		return `<span class="cpd-inline-svg ${largeCls}" title="${type + " " + element.id}">${type} <svg viewBox="-5 -5 ${width} ${height}">${result}</svg></span>`;
	}
}