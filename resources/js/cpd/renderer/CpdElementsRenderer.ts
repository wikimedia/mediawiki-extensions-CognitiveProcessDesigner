import BaseRenderer from "diagram-js/lib/draw/BaseRenderer";
import BpmnRenderer from "bpmn-js/lib/draw/BpmnRenderer";
import CpdElement from "../model/CpdElement";
import { Shape } from "bpmn-js/lib/model/Types";
import { ShapeLike } from "bpmn-js/lib/draw/BpmnRenderUtil";

export default class CpdElementsRenderer extends BaseRenderer {
	// Need to inject due to webpack failing to resolve the dependencies on production mode
	static $inject = [ 'eventBus', 'bpmnRenderer' ];

	private static readonly HIGHLIGHT_COLOR = "#36c";

	private bpmnRenderer: BpmnRenderer;

	constructor( eventBus, bpmnRenderer ) {
		super( eventBus, 1500 );
		this.bpmnRenderer = bpmnRenderer;
	}

	canRender( element: CpdElement | Element ): boolean {
		return element instanceof CpdElement;
	}

	drawShape( parentNode: SVGElement, element: CpdElement | ShapeLike ): SVGElement {
		if ( !( element instanceof CpdElement ) ) {
			return this.bpmnRenderer.drawShape( parentNode, element );
		}

		const shape = this.bpmnRenderer.drawShape( parentNode, element.bpmnElement as Shape );
		shape.style.cursor = "pointer";
		shape.style.stroke = CpdElementsRenderer.HIGHLIGHT_COLOR;

		return shape;
	}
}
