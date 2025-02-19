import { Element } from "bpmn-js/lib/model/Types";

export interface CpdElementJson {
	id: string;
	type: string;
	label: string | null;
	parent: CpdElement | null;
	descriptionPage: string | null;
	oldDescriptionPage: string | null;
	incomingLinks: CpdElementJson[];
	outgoingLinks: CpdElementJson[];
}

interface ElementDescriptionPage {
	exists: boolean;
	dbKey: string;
	oldDbKey?: string | undefined;
}

export default class CpdElement {
	public readonly bpmnElement: Element;

	public descriptionPage: ElementDescriptionPage | null = null;

	public incomingLinks: CpdElementJson[] = [];

	public outgoingLinks: CpdElementJson[] = [];

	private constructor( bpmnElement: Element ) {
		this.bpmnElement = bpmnElement;
		this.descriptionPage = null;
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

	public getDescriptionPageUrl(): string | null {
		if ( !this.descriptionPage?.dbKey ) {
			return null;
		}

		return this.createLinkFromDbKey( this.descriptionPage.dbKey );
	}

	public getOldDescriptionPageUrl(): string | null {
		if ( !this.descriptionPage?.oldDbKey ) {
			return null;
		}

		return this.createLinkFromDbKey( this.descriptionPage.oldDbKey );
	}

	private createLinkFromDbKey( dbKey: string ): string {
		const linkText = dbKey.split( '/' ).pop();
		return `<a target="_blank" href="${ mw.util.getUrl( dbKey ) }">${ linkText }</a>`;
	}

	public toJSON(): CpdElementJson {
		return {
			id: this.id,
			type: this.type,
			label: this.label,
			parent: this.parent,
			descriptionPage: this.descriptionPage?.dbKey,
			oldDescriptionPage: this.descriptionPage?.oldDbKey,
			incomingLinks: this.incomingLinks,
			outgoingLinks: this.outgoingLinks
		};
	}
}
