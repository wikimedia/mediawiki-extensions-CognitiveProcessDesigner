import EventBus from "diagram-js/lib/core/EventBus";
import EventEmitter from "events";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";

interface LinterEvent {
	issues: {
		[ element: string ]: LinterIssue[];
	};
}

interface LinterIssue {
	id: string;
	message: string;
	category: string;
	rule: string;
}

export default class CpdValidator extends EventEmitter {
	public static readonly VALIDATION_EVENT: string = "validation";

	private static readonly ELEMENT_PRE_RENAME: string = "commandStack.element.updateLabel.preExecute";

	private readonly elementRegistry: ElementRegistry;

	public constructor( eventBus: EventBus, elementRegistry: ElementRegistry ) {
		super();

		this.elementRegistry = elementRegistry;

		eventBus.on( "linting.completed", ( event: LinterEvent ) => {
			const isValid = this.getValidation( Object.values( event.issues ) );
			this.emit( CpdValidator.VALIDATION_EVENT, isValid );
		} );

		eventBus.on( CpdValidator.ELEMENT_PRE_RENAME, this.handleDuplicateLabel.bind( this ) );
	}

	private getValidation( issues: LinterIssue[][] ): boolean {
		if ( issues.length === 0 ) {
			return true;
		}

		return issues.every( ( elementIssues: LinterIssue[] ) => elementIssues.every(
			( issue: LinterIssue ) => issue.category !== "error" )
		);
	}

	/**
	 * ERM39691 Modify duplicate label until proposed linter rule change is accepted by bpmn-io
	 * @param event
	 */
	private handleDuplicateLabel( event: Event ): void {
		let newLabel = event[ 'context' ].newLabel;
		if ( !newLabel ) {
			return;
		}

		while ( !this.isLabelUnique( newLabel ) ) {
			newLabel = this.incrementOrInsertParenthesisNumber( newLabel );
		}

		event[ 'context' ].newLabel = newLabel;
	}

	private incrementOrInsertParenthesisNumber( str: string ): string {
		const matches = str.match( /\((\d+)\)/g ) || [];
		const numbers = matches.map( ( match ) => parseInt( match.replace( /\D/g, "" ), 10 ) );
		const validNumbers = numbers.filter( ( num ) => num > 1 );

		if ( validNumbers.length > 0 ) {
			const lastNumber = validNumbers[ validNumbers.length - 1 ];
			const newNumber = lastNumber + 1;
			return str.replace( `(${ lastNumber })`, `(${ newNumber })` );
		}

		return str.trim() + " (2)";
	}

	private isLabelUnique( label: string ): boolean {
		const existingElements = this.elementRegistry.filter(
			( element ) => element.businessObject.name === label
		);

		return existingElements.length === 0;
	}

}
