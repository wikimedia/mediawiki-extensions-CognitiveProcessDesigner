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

		const path = svgCreate( "path", {
			fill: "#000000",
			d: "M12 4C8 4 5 7 5 11s3 7 7 7 7-3 7-7-3-7-7-7zm0 12c-.56 0-1-.45-1-1s.45-1 1-1c.56 0 1 .45 1 1s-.45 1-1 1zM11 8h2v4h-2zm0 7h2v2h-2z"
		} );

		shape.after( path, shape.firstChild );

		return shape;
	}
}
