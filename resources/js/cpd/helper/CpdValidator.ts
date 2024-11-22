import EventBus from "diagram-js/lib/core/EventBus";
import EventEmitter from "events";

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

	public constructor( eventBus: EventBus ) {
		super();

		eventBus.on( "linting.completed", ( event: LinterEvent ) => {
			const isValid = this.getValidation( Object.values( event.issues ) );
			this.emit( CpdValidator.VALIDATION_EVENT, isValid );
		} );

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
}
