import ElementRegistry, { ElementRegistryFilterCallback } from "diagram-js/lib/core/ElementRegistry";
import { ElementLike } from "diagram-js/lib/core/Types";
import { Element, Shape } from "bpmn-js/lib/model/Types";
import CpdElement from "../model/CpdElement";
import { ExistingDescriptionPage } from "../CpdTool";
import { CpdConnectionFinder } from "./CpdConnectionFinder";

export class CpdElementFactory {
	private readonly subpageTypes: Array<string>;
	private readonly cpdLaneTypes: Array<string>;
	private readonly processNamespace: number;
	private readonly process: string;
	private elementRegistry: ElementRegistry;
	private readonly existingDescriptionPages: ExistingDescriptionPage[];
	private readonly connectionFinder: CpdConnectionFinder;

	public constructor(
		elementRegistry: ElementRegistry,
		process: string,
		existingDescriptionPages: ExistingDescriptionPage[]
	) {
		this.elementRegistry = elementRegistry;
		this.process = process;
		this.subpageTypes = mw.config.get( "cpdDedicatedSubpageTypes" );
		this.cpdLaneTypes = mw.config.get( "cpdLaneTypes" );
		this.processNamespace = mw.config.get( "cpdProcessNamespace" );
		this.existingDescriptionPages = existingDescriptionPages;
		this.connectionFinder = new CpdConnectionFinder( this.subpageTypes );

		if ( !this.subpageTypes ) {
			throw new Error( mw.message(
				"cpd-error-message-missing-config",
				"cpdDedicatedSubpageTypes" ).text()
			);
		}

		if ( !this.cpdLaneTypes ) {
			throw new Error( mw.message(
				"cpd-error-message-missing-config",
				"cpdLaneTypes" ).text()
			);
		}

		if ( !this.processNamespace ) {
			throw new Error( mw.message(
				"cpd-error-message-missing-config",
				"cpdProcessNamespace" ).text()
			);
		}
	}

	public createFromShape( shape: Shape ): CpdElement {
		const cpdElement = CpdElement.init( shape );
		this.addDescriptionPageProperty( cpdElement );

		return cpdElement;
	}

	public createElements(): CpdElement[] {
		return this.elementRegistry.getAll().map( ( element: Element ) => CpdElement.init( element ) );
	}

	public createDescriptionPageEligibleElements(): CpdElement[] {
		const cpdElements = this.findDescriptionPageEligibleElements( this.subpageTypes ).map(
			( element: Element ): CpdElement => {
				const cpdElement = CpdElement.init( element );
				this.addDescriptionPageProperty( cpdElement );

				return cpdElement;
			}
		);

		cpdElements.forEach( ( element: CpdElement ) => this.addConnections( element, cpdElements ) );

		return cpdElements;
	}

	public findElementsWithExistingDescriptionPage(): CpdElement[] {
		return this.createDescriptionPageEligibleElements()
			.filter( ( element: CpdElement ) => element.descriptionPage?.exists );
	}

	public findInitialElement(): CpdElement {
		const initialElement = this.findDescriptionPageEligibleElements( [ "bpmn:StartEvent" ] );

		if ( initialElement.length !== 1 ) {
			throw new Error( mw.message( "cpd-error-message-missing-initial-element" ).text() );
		}

		return CpdElement.init( initialElement[ 0 ] );
	}

	public getSVGElement( element: CpdElement ): SVGElement {
		return this.elementRegistry.getGraphics( element.bpmnElement );
	}

	private addDescriptionPageProperty( element: CpdElement ): void {
		if ( !this.subpageTypes.includes( element.type ) ) {
			element.descriptionPage = null;
			return;
		}

		try {
			const madeDbKey = this.makeDescriptionPageTitle( element ).getPrefixedDb();
			const existingDescriptionPage = this.existingDescriptionPages.find( ( page: ExistingDescriptionPage ) => page.dbKey === madeDbKey );

			if ( existingDescriptionPage ) {
				element.descriptionPage = {
					dbKey: existingDescriptionPage.dbKey,
					isNew: existingDescriptionPage.isNew,
					exists: true
				};
			} else {
				element.descriptionPage = { dbKey: madeDbKey, isNew: false, exists: false };
			}
		} catch ( error ) {
			console.warn( error );
			element.descriptionPage = null;
		}
	}

	private addConnections( cpdElement: CpdElement, cpdElements: CpdElement[] ): void {
		const incomingLinks = [];
		const outgoingLinks = [];
		this.connectionFinder.findIncomingConnections( cpdElement.bpmnElement, incomingLinks );
		this.connectionFinder.findOutgoingConnections( cpdElement.bpmnElement, outgoingLinks );

		const linkToCpdElement = ( link: string ): CpdElement => {
			const foundElement = cpdElements.find( ( cpdElement: CpdElement ) => cpdElement.id === link );
			if ( !foundElement ) {
				throw new Error( mw.message( "cpd-error-message-missing-element", link ).text() );
			}

			return foundElement;
		}

		cpdElement.incomingLinks = incomingLinks.map( linkToCpdElement );
		cpdElement.outgoingLinks = outgoingLinks.map( linkToCpdElement );
	}

	private makeDescriptionPageTitle( element: CpdElement ): mw.Title {
		if ( !element.label ) {
			throw new Error( mw.message( "cpd-error-message-missing-label", element.id ).text() );
		}

		let title: mw.Title;

		if ( element.parent?.label && this.cpdLaneTypes.includes( element.parent?.type ) ) {
			title = mw.Title.newFromText( `${ this.process }/${ element.parent.label }/${ element.label }`, this.processNamespace );
		} else {
			title = mw.Title.newFromText( `${ this.process }/${ element.label }`, this.processNamespace );
		}

		if ( !title ) {
			throw new Error( mw.message( "cpd-error-could-not-create-title", element.id ).text() );
		}

		return title;
	}

	private findDescriptionPageEligibleElements( types: string[] ): Element[] {
		const filter: ElementRegistryFilterCallback = ( element: ElementLike ) => types.includes( element.type );
		return this.elementRegistry.filter( filter ) as unknown as Element[];
	}
}
