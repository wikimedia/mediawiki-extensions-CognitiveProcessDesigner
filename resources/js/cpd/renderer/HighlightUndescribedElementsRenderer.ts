import BaseRenderer from "diagram-js/lib/draw/BaseRenderer";
import EventBus from "diagram-js/lib/core/EventBus";
import { create as svgCreate } from "tiny-svg";
import BpmnRenderer from "bpmn-js/lib/draw/BpmnRenderer";
import CpdElement from "../model/CpdElement";

export default class HighlightUndescribedElementsRenderer extends BaseRenderer {
	private bpmnRenderer: BpmnRenderer;

	constructor( eventBus: EventBus, bpmnRenderer: BpmnRenderer ) {
		super( eventBus, 1500 );
		this.bpmnRenderer = bpmnRenderer;
	}

	canRender( element: CpdElement|Element ): boolean {
		if ( !( element instanceof CpdElement ) ) {
			return false;
		}

		if ( !element.descriptionPage ) {
			return false;
		}

		return element.descriptionPage.isNew;
	}

	drawShape( parentNode: SVGElement, element: any ): SVGElement {
		if ( !( element instanceof CpdElement ) ) {
			return this.bpmnRenderer.drawShape( parentNode, element );
		}

		const shape = this.bpmnRenderer.drawShape( parentNode, element.bpmnElement );

		const padding = 5;
		const outline = svgCreate("rect", {
			fill: "none",
			stroke: "#0d6efd",
			"stroke-width": 1,
			x: -padding,
			y: -padding,
			width: element.bpmnElement.width + padding * 2,
			height: element.bpmnElement.height + padding * 2
		});

		shape.after( outline, shape.firstChild );

		return shape;
	}
}
