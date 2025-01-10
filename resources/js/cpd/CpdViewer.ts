import BpmnViewer from "bpmn-js/lib/Viewer";
import { ModdleElement } from "bpmn-js/lib/model/Types";
// eslint-disable-next-line no-unused-vars
import config from "types-mediawiki/mw/config";
// eslint-disable-next-line no-unused-vars
import hook from "types-mediawiki/mw/hook";
import EventBus from "diagram-js/lib/core/EventBus";
import { CpdTool } from "./CpdTool";
import NavigatedViewer from "bpmn-js/lib/NavigatedViewer";
import CpdElementsRenderer from "./renderer/CpdElementsRenderer";
import CpdElement from "./model/CpdElement";
import { CpdElementFactory } from "./helper/CpdElementFactory";

interface InternalEvent {
	element: ModdleElement;
	gfx: SVGElement;
	orginalEvent: Event;
	type: string;
}

export default class CpdViewer extends CpdTool {
	private bpmnViewer: BpmnViewer;

	private eventBus: EventBus;

	public constructor( process: string, container: HTMLElement ) {
		super( process, container );

		this.dom.initDomElements( false );
		this.dom.on( "showXml", this.onShowXml.bind( this ) );

		this.bpmnViewer = new NavigatedViewer( {
			additionalModules: [
				{
					__init__: [ "customRenderer" ],
					customRenderer: [ "type", CpdElementsRenderer ]
				}
			]
		} );
		this.eventBus = this.bpmnViewer.get( "eventBus" );

		this.renderDiagram( process );
	}

	protected async renderDiagram( process: string ): Promise<void> {
		await this.initPageContent();

		this.elementFactory = new CpdElementFactory( this.bpmnViewer.get( "elementRegistry" ), process, this.descriptionPages );

		if ( !this.xml ) {
			this.handleNotInitializedDiagram( process );
			return;
		}

		await this.attachToCanvas( this.bpmnViewer );
		await this.initDescriptionPageElements();
	}

	private async initDescriptionPageElements(): Promise<void> {
		const elements = this.elementFactory.findElementsWithExistingDescriptionPage();
		this.addDescriptionPageElementListener( elements );
		this.rerenderCpdElements( elements );
	}

	private rerenderCpdElements( elements: CpdElement[] ): void {
		elements.forEach( ( element: CpdElement ): void => {
			if ( !element.descriptionPage ) {
				return;
			}

			const visual = this.elementFactory.getSVGElement( element );
			this.eventBus.fire( "render.shape", { gfx: visual, element } );
		} );
	}

	private addDescriptionPageElementListener( elements: CpdElement[] ): void {
		const findCpdElement = ( e: InternalEvent ): CpdElement | null => {
			const cpdElement = elements.find(
				( element: CpdElement ): boolean => element.id === e.element.id
			);

			if ( !cpdElement || !cpdElement.descriptionPage ) {
				return null;
			}

			return cpdElement;
		};

		this.eventBus.on( "element.click", ( e: InternalEvent ) => {
			const cpdElement = findCpdElement( e );
			if ( !cpdElement ) {
				return;
			}

			window.open( this.createDescriptionPageLink( cpdElement, mw.config.get( "wgPageName" ) ), "_blank" );
		} );
	}

	private createDescriptionPageLink( element: CpdElement, returnToParam: string = "" ): string {
		if ( returnToParam === "" ) {
			return mw.util.getUrl( element.descriptionPage.dbKey );
		}

		return mw.util.getUrl( element.descriptionPage.dbKey, { [ mw.config.get( "cpdReturnToQueryParam" ) ]: returnToParam } );
	}

	private async onShowXml(): Promise<void> {
		const syntaxHighlightedXml = await this.api.fetchSyntaxHighlightedXml( this.xml );
		this.dom.showXml( syntaxHighlightedXml );
	}

	private handleNotInitializedDiagram( process: string ): void {
		this.dom.disableShowXmlButton();
		this.dom.disableSvgLink();

		const currentPage = mw.Title.newFromText( mw.config.get( "wgPageName" ) );

		if ( currentPage.getPrefixedDb() !== this.diagramPage.getPrefixedDb() ) {
			this.dom.showWarning( mw.message(
				"cpd-warning-message-diagram-not-initialized",
				process.replace( /_/g, " " ),
				this.diagramPage.getUrl( { action: 'edit' } ) ).text()
			);

			return;
		}

		this.dom.showWarning( mw.message(
			"cpd-warning-message-diagram-not-initialized-create-it",
			this.diagramPage.getUrl( { action: 'edit' } ) ).text()
		);
	}
}

Object.keys( mw.config.get( "cpdProcesses" ) ).forEach( ( process: string ): void => {
	document.querySelectorAll( `[data-process=${ process }]` ).forEach( ( container: HTMLElement ): void => {
		new CpdViewer( process, container );
	} );
} );
