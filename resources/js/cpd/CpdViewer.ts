import BpmnViewer from "bpmn-js/lib/Viewer";
import { ModdleElement } from "bpmn-js/lib/model/Types";
// eslint-disable-next-line no-unused-vars
// noinspection ES6UnusedImports
import config from "types-mediawiki/mw/config";
import EventBus from "diagram-js/lib/core/EventBus";
import Canvas from "diagram-js/lib/core/Canvas";
import { CpdTool } from "./CpdTool";
import NavigatedViewer from "bpmn-js/lib/NavigatedViewer";
import HighlightUndescribedElementsRenderer from "./renderer/HighlightUndescribedElementsRenderer";
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

	public constructor( process: string ) {
		super( process );

		this.dom.initDomElements( false );
		this.dom.on( "showXml", this.onShowXml.bind( this ) );

		this.bpmnViewer = new NavigatedViewer( {
			additionalModules: [
				{
					__init__: [ "customRenderer" ],
					customRenderer: [ "type", HighlightUndescribedElementsRenderer ]
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
			this.dom.showWarning( mw.message(
				"cpd-warning-message-diagram-not-initialized",
				this.diagramPage.getUrl( {} ) ).text()
			);
			this.dom.disableShowXmlButton();
			this.dom.disableSvgLink();

			return;
		}

		this.bpmnViewer.attachTo( this.dom.getCanvas() );

		try {
			await this.bpmnViewer.importXML( this.xml );
		} catch ( e ) {
			this.dom.showError( e );
		}

		const canvas = this.bpmnViewer.get( "canvas" ) as Canvas;
		canvas.zoom( "fit-viewport" );

		this.initDescriptionPageElements();
	}

	private async initDescriptionPageElements(): Promise<void> {
		const elements = this.elementFactory.findElementsWithExistingDescriptionPage();
		this.addDescriptionPageElementListener( elements );
		this.highlightUndescribedElements( elements );
	}

	private highlightUndescribedElements( elements: CpdElement[] ): void {
		elements.forEach( ( element: CpdElement ): void => {
			if ( !element.descriptionPage ) {
				return;
			}

			// Only rerender the element if the description page has not been edited
			if ( !element.descriptionPage.isNew ) {
				return;
			}

			const visual = this.elementFactory.getSVGElement( element );
			this.eventBus.fire( "render.shape", { gfx: visual, element } );
		} );
	}

	private addDescriptionPageElementListener( elements: CpdElement[] ): void {
		this.eventBus.on( "element.click", ( e: InternalEvent ) => {
			const cpdElement = elements.find(
				( element: CpdElement ): boolean => element.id === e.element.id
			);
			if ( !cpdElement ) {
				return;
			}

			if ( cpdElement.descriptionPage ) {
				const url = this.createDescriptionPageLink( cpdElement, mw.config.get( "wgPageName" ) );
				window.open( url, "_blank" );
			}
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
}

new CpdViewer( mw.config.get( "cpdProcess" ) );
