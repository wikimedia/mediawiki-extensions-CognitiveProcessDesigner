import BaseRenderer from "diagram-js/lib/draw/BaseRenderer";
import EventBus from "diagram-js/lib/core/EventBus";
import BpmnRenderer from "bpmn-js/lib/draw/BpmnRenderer";
import CpdElement from "../model/CpdElement";

export default class CpdElementsRenderer extends BaseRenderer {
	private static readonly HIGHLIGHT_COLOR = "#0d6efd";

	private bpmnRenderer: BpmnRenderer;

	constructor( eventBus: EventBus, bpmnRenderer: BpmnRenderer ) {
		super( eventBus, 1500 );
		this.bpmnRenderer = bpmnRenderer;
	}

	canRender( element: CpdElement | Element ): boolean {
		if ( !( element instanceof CpdElement ) ) {
			return false;
		}

		if ( !element.descriptionPage ) {
			return false;
		}

		return true;
	}

	drawShape( parentNode: SVGElement, element: any ): SVGElement {
		if ( !( element instanceof CpdElement ) ) {
			return this.bpmnRenderer.drawShape( parentNode, element );
		}

		const shape = this.bpmnRenderer.drawShape( parentNode, element.bpmnElement );
		shape.style.cursor = "pointer";
		shape.style.stroke = CpdElementsRenderer.HIGHLIGHT_COLOR;

		return shape;
	}
}
