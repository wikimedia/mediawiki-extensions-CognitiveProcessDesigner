import CpdElement from "../model/CpdElement";
import CpdInlineSvgRenderer from "./CpdInlineSvgRenderer";

export interface ValidationState {
	readonly isValid: boolean;
	readonly messages: string[];
}

export default class CpdValidator {

	private svgRenderer: CpdInlineSvgRenderer;

	public constructor( svgRenderer: CpdInlineSvgRenderer ) {
		this.svgRenderer = svgRenderer;
	}

	public validate( elements: CpdElement[] ): ValidationState {
		// Validate every element has a label
		const missingLabelsState = this.checkMissingLabels( elements );
		// Validate there are no duplicate labels
		const duplicateLabelsState = this.checkDuplicateLabels( elements );

		const isValid = missingLabelsState.isValid && duplicateLabelsState.isValid;
		const messages = [];
		messages.push( ...missingLabelsState.messages, ...duplicateLabelsState.messages );

		return {
			isValid,
			messages
		};
	}

	private checkMissingLabels( elements: CpdElement[] ): ValidationState {
		const messages = [];
		let isValid = true;

		elements.forEach( ( element: CpdElement ): void => {
			if ( element.label === null ) {
				messages.push( mw.msg( "cpd-validation-missing-label", this.svgRenderer.getSVGFromElement( element ) ) );
				isValid = false;
			}
		} );

		return {
			isValid,
			messages
		};
	}

	private checkDuplicateLabels( elements: CpdElement[] ): ValidationState {
		const messages = [];
		const labels = [];
		let isValid = true;

		elements.forEach( ( element: CpdElement ): void => {
			if ( element.label === null ) {
				return;
			}

			if ( !labels[ element.label ] ) {
				labels[ element.label ] = [];
			}

			labels[ element.label ].push( element.id );
		} );

		Object.entries( labels ).forEach( ( [ label, ids ] ): void => {
			if ( ids.length < 2 ) {
				return;
			}

			const svgs = ids.map( ( id: string ): string => this.svgRenderer.getSVGFromElement( elements.find( ( element: CpdElement ): boolean => element.id === id ) ) );
			messages.push( mw.msg( "cpd-validation-duplicate-label", label, svgs.join( ", " ) ) );
			isValid = false;
		} );

		return {
			isValid,
			messages
		};
	}
}
