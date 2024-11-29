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

		return issues.every( ( elementIssues: LinterIssue[] ) => {
			return elementIssues.every( ( issue: LinterIssue ) => {
				return issue.category !== "error";
			} );
		} );
	}

	/**
	 * ERM39691 Modify duplicate label until proposed linter rule change is accepted by bpmn-io
	 *
	 * @param event
	 * @private
	 */
	private handleDuplicateLabel( event: Event ): void {
		const newLabel = event[ 'context' ].newLabel;

		const existingElements = this.elementRegistry.filter( ( element ) =>
			element.businessObject.name === newLabel
		);

		if ( existingElements.length === 0 ) {
			return;
		}

		event[ 'context' ].newLabel = `${ newLabel } (${ event[ 'context' ].element.id })`;
	}
}
