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
	descriptionPage?: string
}

export class CpdElementFactory {
	private readonly subpageTypes: Array<string>;

	private elementRegistry: ElementRegistry;

	public constructor( elementRegistry: ElementRegistry ) {
		this.elementRegistry = elementRegistry;
		this.subpageTypes = mw.config.get( "cpdDedicatedSubpageTypes" ) as Array<string>;

		if ( !this.subpageTypes ) {
			throw new Error( mw.message(
				"cpd-error-message-missing-config",
				"cpdDedicatedSubpageTypes" ).text()
			);
		}
	}

	public createCpdElement( data: Shape | Element ): CpdElement {
		return CpdElement.init( data );
	}

	public createDescriptionPageEligibleElements(): CpdElement[] {
		return this.findDescriptionPageEligibleElements( this.subpageTypes ).map(
			( element: Element ): CpdElement => this.createCpdElement( element ) );
	}

	public getSVGElement( element: CpdElement ): SVGElement {
		return this.elementRegistry.getGraphics( element.bpmnElement );
	}

	private findDescriptionPageEligibleElements( types: string[] ): Element[] {
		const filter: ElementRegistryFilterCallback = ( element: ElementLike ) => types.includes( element.type );
		return this.elementRegistry.filter( filter ) as unknown as Element[];
	}
}
