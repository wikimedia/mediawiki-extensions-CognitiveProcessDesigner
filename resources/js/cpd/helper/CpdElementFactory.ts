import ElementRegistry, {
	ElementRegistryFilterCallback
} from "diagram-js/lib/core/ElementRegistry";
import { ElementLike } from "diagram-js/lib/core/Types";
import { Element, Shape } from "bpmn-js/lib/model/Types";
import CpdElement from "../model/CpdElement";

export interface CpdElementJson {
	id: string;
	type: string;
	label: string;
	descriptionPage: string;
}

export class CpdElementFactory {
	private readonly subpageTypes: Array<string>;

	private readonly processNamespace: number;

	private elementRegistry: ElementRegistry;

	private existingDescriptionPages: string[];

	public constructor(
		elementRegistry: ElementRegistry,
		existingDescriptionPages: string[]
	) {
		this.elementRegistry = elementRegistry;
		this.subpageTypes = mw.config.get( "cpdDedicatedSubpageTypes" ) as Array<string>;
		this.processNamespace = mw.config.get( "cpdProcessNamespace" ) as number;
		this.existingDescriptionPages = existingDescriptionPages;

		if ( !this.subpageTypes ) {
			throw new Error( mw.message(
				"cpd-error-message-missing-config",
				"cpdDedicatedSubpageTypes" ).text()
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
		return this.findDescriptionPageEligibleElements( this.subpageTypes ).map(
			( element: Element ): CpdElement => this.createCpdElement( element ) );
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

	public setExistingDescriptionPages( descriptionPages: string[] ): void {
		this.existingDescriptionPages = descriptionPages;
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

	private findDescriptionPageEligibleElements( types: string[] ): Element[] {
		const filter: ElementRegistryFilterCallback = ( element: ElementLike ) => types.includes( element.type );
		return this.elementRegistry.filter( filter ) as unknown as Element[];
	}
}
