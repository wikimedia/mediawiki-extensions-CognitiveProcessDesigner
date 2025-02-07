import ElementRegistry, {
	ElementRegistryFilterCallback
} from "diagram-js/lib/core/ElementRegistry";
import { ElementLike } from "diagram-js/lib/core/Types";
import { Element, Shape } from "bpmn-js/lib/model/Types";
import CpdElement, { CpdElementJson } from "../model/CpdElement";
import { CpdConnectionFinder } from "./CpdConnectionFinder";

export class CpdElementFactory {
	private readonly subpageTypes: Array<string>;

	private readonly cpdLaneTypes: Array<string>;

	private readonly processNamespace: number;

	private readonly process: string;

	private elementRegistry: ElementRegistry;

	private readonly existingDescriptionPages: string[];

	private readonly connectionFinder: CpdConnectionFinder;

	public constructor(
		elementRegistry: ElementRegistry,
		process: string,
		existingDescriptionPages: string[]
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

	public createCpdElement( data: Shape | Element ): CpdElement {
		const cpdElement = CpdElement.init( data );
		this.addDescriptionPageProperty( cpdElement );

		return cpdElement;
	}

	public createElements(): CpdElement[] {
		return this.elementRegistry.getAll().map(
			( element: Element ) => this.createCpdElement( element )
		);
	}

	public createDescriptionPageEligibleElements(): CpdElement[] {
		const cpdElements = this.findDescriptionPageEligibleElements( this.subpageTypes ).map(
			( element: Element ): CpdElement => this.createCpdElement( element ) );

		cpdElements.forEach(
			( element: CpdElement ) => this.addConnections( element, cpdElements )
		);

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

		return this.createCpdElement( initialElement[ 0 ] );
	}

	public getSVGElement( element: CpdElement ): SVGElement {
		return this.elementRegistry.getGraphics( element.bpmnElement );
	}

	public isDescriptionPageEligible( element: CpdElement ): boolean {
		return this.subpageTypes.includes( element.type );
	}

	private addDescriptionPageProperty( element: CpdElement ): void {
		if ( !this.isDescriptionPageEligible( element ) || !element.label ) {
			return;
		}

		const pageTitle = this.makeDescriptionPageTitle( element );
		const madeDbKey = pageTitle.getPrefixedDb();

		const existingDescriptionPage = this.existingDescriptionPages.find(
			( page: string ) => page === madeDbKey
		);

		if ( existingDescriptionPage ) {
			element.descriptionPage = {
				dbKey: existingDescriptionPage,
				exists: true
			};
		} else {
			element.descriptionPage = { dbKey: madeDbKey, exists: false };
		}
	}

	private addConnections( cpdElement: CpdElement, cpdElements: CpdElement[] ): void {
		const incomingLinks = [];
		const outgoingLinks = [];
		this.connectionFinder.findIncomingConnections( cpdElement.bpmnElement, incomingLinks );
		this.connectionFinder.findOutgoingConnections( cpdElement.bpmnElement, outgoingLinks );

		const linkToCpdElement = ( link: string ): CpdElementJson => {
			const foundElement = cpdElements.find( ( el: CpdElement ) => el.id === link );
			if ( !foundElement ) {
				throw new Error( mw.message( "cpd-error-message-missing-element", link ).text() );
			}

			const foundElementJson = foundElement.toJSON();
			foundElementJson.incomingLinks = [];
			foundElementJson.outgoingLinks = [];

			return foundElementJson;
		};

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
