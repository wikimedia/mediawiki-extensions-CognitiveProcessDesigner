import { Element } from "bpmn-js/lib/model/Types";

export default class CpdElement {
	public readonly bpmnElement: Element;

	private constructor( bpmnElement: Element ) {
		this.bpmnElement = bpmnElement;
	}

	public static init( bpmnElement: Element ): CpdElement {
		return new CpdElement( bpmnElement );
	}

	get id(): string {
		return this.bpmnElement.id;
	}

	get type(): string {
		return this.bpmnElement.type;
	}

	get label(): string | null {
		let label = this.bpmnElement?.businessObject?.name;

		if ( label === undefined || label === null ) {
			return null;
		}

		// Replace all newlines (\n) with spaces
		label = label.replace( /\n/g, ' ' );
		return label;
	}

	set label( label: string ) {
		this.bpmnElement.businessObject.name = label;
	}

	get parent(): CpdElement | null {
		// Ignore bpmn collaboration type
		if ( this.bpmnElement.parent?.type === 'bpmn:Collaboration' ) {
			return null;
		}

		return this.bpmnElement.parent ?
			new CpdElement( this.bpmnElement.parent as Element ) : null;
	}

	set parent( parent: CpdElement | null ) {
		this.bpmnElement.parent = parent?.bpmnElement;
	}
}
