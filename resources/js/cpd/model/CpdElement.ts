import { Connection, Element } from "bpmn-js/lib/model/Types";
import { ExistingDescriptionPage } from "../CpdTool";

export interface CpdElementJson {
	id: string;
	type: string;
	label: string | null;
	parent: CpdElement | null;
	descriptionPage: string | null;
	oldDescriptionPage: string | null;
	incomingLinks: string[];
	outgoingLinks: string[];
}

interface ElementDescriptionPage extends ExistingDescriptionPage {
	exists: boolean;
	oldDbKey?: string | undefined;
}

export default class CpdElement {
	public readonly bpmnElement: Element;
	public descriptionPage: ElementDescriptionPage | null = null;
	public incomingLinks: CpdElement[] = [];
	public outgoingLinks: CpdElement[] = [];

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
		return this.bpmnElement.parent ?
			new CpdElement( this.bpmnElement.parent as Element ) : null;
	}

	set parent( parent: CpdElement | null ) {
		this.bpmnElement.parent = parent?.bpmnElement;
	}

	public getDescriptionPageUrl(): string | null {
		if ( !this.descriptionPage?.dbKey ) {
			return null;
		}

		const linkText = this.descriptionPage.dbKey.split( '/' ).pop();

		return `<a target="_blank" href="${ mw.util.getUrl( this.descriptionPage.dbKey ) }">${ linkText }</a>`;
	}

	public getOldDescriptionPageUrl(): string | null {
		if ( !this.descriptionPage?.oldDbKey ) {
			return null;
		}

		return `<a target="_blank" href="${ mw.util.getUrl( this.descriptionPage.oldDbKey ) }">${ this.descriptionPage.oldDbKey }</a>`;
	}

	public toJSON(): CpdElementJson {
		return {
			id: this.id,
			type: this.type,
			label: this.label,
			parent: this.parent,
			descriptionPage: this.descriptionPage?.dbKey,
			oldDescriptionPage: this.descriptionPage?.oldDbKey,
			incomingLinks: this.incomingLinks.map( ( link: CpdElement ) => link.descriptionPage?.dbKey ),
			outgoingLinks: this.outgoingLinks.map( ( link: CpdElement ) => link.descriptionPage?.dbKey )
		};
	}
}
