import EventBus from "diagram-js/lib/core/EventBus";
import CpdElement from "../model/CpdElement";
import EventEmitter from "events";
import { CpdElementFactory } from "./CpdElementFactory";
import CpdInlineSvgRenderer from "./CpdInlineSvgRenderer";
import { Shape } from "bpmn-js/lib/model/Types";

export interface MessageObject {
	message: string;
	onlyWithPages: boolean;
}

export type ChangeLogMessages = {
	[ id: string ]: MessageObject[];
};

export default class CpdChangeLogger extends EventEmitter {
	private static readonly ELEMENT_CREATE: string = "commandStack.elements.create.postExecute";
	private static readonly ELEMENT_DELETE: string = "commandStack.elements.delete.preExecute";
	private static readonly ELEMENT_RENAME: string = "commandStack.element.updateLabel.executed";
	private static readonly DIAGRAM_CHANGED: string = "commandStack.changed";
	private deletions: ChangeLogMessages;
	private creations: ChangeLogMessages;
	private renames: ChangeLogMessages;
	private factory: CpdElementFactory;
	private svgRenderer: CpdInlineSvgRenderer;

	public constructor( eventBus: EventBus, factory: CpdElementFactory, svgRenderer: CpdInlineSvgRenderer ) {
		super();

		eventBus.on( CpdChangeLogger.ELEMENT_CREATE, this.onElementsChanged.bind( this, CpdChangeLogger.ELEMENT_CREATE ) );
		eventBus.on( CpdChangeLogger.ELEMENT_DELETE, this.onElementsChanged.bind( this, CpdChangeLogger.ELEMENT_DELETE ) );
		eventBus.on( CpdChangeLogger.ELEMENT_RENAME, this.onElementChanged.bind( this, CpdChangeLogger.ELEMENT_RENAME ) );
		eventBus.on( CpdChangeLogger.DIAGRAM_CHANGED, this.onDiagramChanged.bind( this ) );

		this.svgRenderer = svgRenderer;
		this.factory = factory;

		this.reset();
	}

	public reset(): void {
		this.deletions = {};
		this.creations = {};
		this.renames = {};
	}

	public getMessages(): ChangeLogMessages {
		this.sanitizeMessages();
		return this.mergeMessages( this.creations, this.deletions, this.renames );
	}

	public addCreation( CpdElement: CpdElement ): void {
		this.onElementCreated( CpdElement );
	}

	public addDescriptionPageChange( element: CpdElement ) {
		if ( element.descriptionPage?.oldDbKey ) {
			this.appendMessage( this.renames, element, mw.message(
				"cpd-shape-rename-with-description-page-message",
				element.getOldDescriptionPageUrl(),
				element.getDescriptionPageUrl()
			).plain(), true );

			return;
		}

		this.appendMessage( this.renames, element, mw.message( "cpd-description-page-creation-message", element.getDescriptionPageUrl() ).plain(), true );
	}

	private onElementChanged( type: string, event: Event ): void {
		const shape = event[ "context" ][ "element" ];

		if ( !shape ) {
			return;
		}

		const element = this.factory.createFromShape( shape );

		if ( type === CpdChangeLogger.ELEMENT_RENAME ) {
			this.onElementRenamed( element, event );
		}
	}

	private onElementsChanged( type: string, event: Event ): void {
		const shapes = event[ "context" ][ "elements" ];

		if ( !shapes ) {
			return;
		}

		shapes.forEach( ( shape: Shape ) => {
			const element = this.factory.createFromShape( shape );

			if ( type === CpdChangeLogger.ELEMENT_DELETE ) {
				this.onElementDeleted( element );
			}

			if ( type === CpdChangeLogger.ELEMENT_CREATE ) {
				this.onElementCreated( element );
			}
		} );
	}

	private onElementCreated( element: CpdElement ): void {
		// Dont add the element if it has been already added
		if ( this.includes( element, this.creations ) ) {
			return;
		}

		this.appendMessage(
			this.creations,
			element,
			mw.message( "cpd-shape-creation-message", this.svgRenderer.getSVGFromElement( element ) ).plain()
		);
	}

	private onElementDeleted( element: CpdElement ): void {
		// Dont add the element if it was created in the same session
		if ( this.includes( element, this.creations ) ) {
			delete this.creations[ element.id ];
			return;
		}

		// Dont add the element if it already added
		if ( this.includes( element, this.deletions ) ) {
			return;
		}

		this.appendMessage( this.deletions, element, mw.message( "cpd-shape-deletion-message", this.svgRenderer.getSVGFromElement( element ) ).plain() );
		if ( element.descriptionPage?.exists ) {
			this.appendMessage( this.deletions, element, mw.message( "cpd-shape-deletion-page-referenced-message", element.getDescriptionPageUrl() ).plain(), true );
		}
	}

	private onElementRenamed( element: CpdElement, event: Event ): void {
		if ( this.includes( element, this.deletions ) ) {
			return;
		}

		const newLabel = event[ "context" ][ "newLabel" ];

		if ( !newLabel ) {
			this.appendMessage(
				this.renames,
				element,
				mw.message( "cpd-shape-remove-label-message", this.svgRenderer.getSVGFromElement( element ) ).plain()
			);

			return;
		}

		this.appendMessage( this.renames, element, mw.message( "cpd-shape-rename-message", this.svgRenderer.getSVGFromElement( element ), newLabel ).plain() );

		if ( element.descriptionPage?.oldDbKey ) {
			this.appendMessage( this.renames, element, mw.message( "cpd-shape-rename-with-description-page-message", element.getDescriptionPageUrl() ).plain(), true );
		}
	}

	private appendMessage( messages: ChangeLogMessages, element: CpdElement, message: string, onlyWithPages: boolean = false ): void {
		// Append message to the messages object only if element is eligible for description page
		if ( !this.factory.isDescriptionPageEligible( element ) ) {
			return;
		}

		if ( !messages[ element.id ] ) {
			messages[ element.id ] = [];
		}

		messages[ element.id ].push( {
			"message": message,
			"onlyWithPages": onlyWithPages
		} );
	}

	private onDiagramChanged(): void {
		this.emit( "diagramChanged" );
	}

	private includes( element: CpdElement, list: ChangeLogMessages ): boolean {
		return list[ element.id ] !== undefined;
	}

	private mergeMessages( ...messages: ChangeLogMessages[] ): ChangeLogMessages {
		const mergedMessages: ChangeLogMessages = {};

		messages.forEach( obj => {
			Object.keys( obj ).forEach( elementId => {
				if ( !mergedMessages[ elementId ] ) {
					mergedMessages[ elementId ] = [];
				}

				mergedMessages[ elementId ] = mergedMessages[ elementId ].concat( obj[ elementId ] );
			} );
		} );

		return mergedMessages;
	}

	private sanitizeMessages(): void {
		// If element has been deleted, it should not be in creations and renames
		Object.keys( this.deletions ).forEach( key => {
			delete this.creations[ key ];
			delete this.renames[ key ];
		} );

		// Remove duplicates
		Object.keys( this.creations ).forEach( key => {
			this.creations[ key ] = this.creations[ key ].filter( ( message, index, self ) => {
				return self.findIndex( m => m.message === message.message ) === index;
			} );
		} );

		Object.keys( this.deletions ).forEach( key => {
			this.deletions[ key ] = this.deletions[ key ].filter( ( message, index, self ) => {
				return self.findIndex( m => m.message === message.message ) === index;
			} );
		} );

		Object.keys( this.renames ).forEach( key => {
			this.renames[ key ] = this.renames[ key ].filter( ( message, index, self ) => {
				return self.findIndex( m => m.message === message.message ) === index;
			} );
		} );
	}
}
