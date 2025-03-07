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
import { LoadDiagramResult } from "./helper/CpdApi";
import { CpdElementJson } from "./helper/CpdElementFactory";

interface InternalEvent {
	element: ModdleElement;
	gfx: SVGElement;
	originalEvent: Event;
	type: string;
}

export default class CpdViewer extends CpdTool {
	private eventBus: EventBus;

	public constructor( process: string, container: HTMLElement, revision: number | null = null ) {
		const bpmnViewer = new NavigatedViewer( {
			additionalModules: [
				{
					__init__: [ "customRenderer" ],
					customRenderer: [ "type", CpdElementsRenderer ]
				}
			]
		} );
		super( process, container, bpmnViewer );

		this.dom.initDomElements( false );
		this.dom.on( "showXml", this.onShowXml.bind( this ) );

		this.eventBus = this.bpmnTool.get( "eventBus" );

		// Remove prerendered image (class `diagram-image-prerendered`) from container
		const prerenderedImage = container.querySelector( ".diagram-image-prerendered" );
		if ( prerenderedImage ) {
			prerenderedImage.remove();
		}

		this.renderDiagram( process, revision );
	}

	private async renderDiagram( process: string, revision: number | null = null ): Promise<void> {
		const pageContent: LoadDiagramResult = await this.api.fetchPageContent( revision );

		pageContent.loadWarnings.forEach( ( warning: string ): void => {
			this.dom.showWarning( warning );
		} );

		this.xml = pageContent.xml;

		if ( !this.xml ) {
			this.handleNotInitializedDiagram( process );

			return;
		}

		await this.attachToCanvas();

		this.dom.setSvgLink( pageContent.svgFile );
		this.updateDescriptionPageElements( pageContent.elements );
	}

	private updateDescriptionPageElements( initialElements: CpdElementJson[] ): void {
		const cpdElements = this.elementFactory.createDescriptionPageEligibleElements();

		const findDescriptionPage = ( id: string ): string | null => {
			const initialElement = initialElements.find(
				( element: CpdElementJson ): boolean => element.id === id
			);

			if ( !initialElement ) {
				return null;
			}

			if ( !initialElement.descriptionPage ) {
				return null;
			}

			return initialElement.descriptionPage;
		};

		this.eventBus.on( "element.click", ( e: InternalEvent ) => {
			const descriptionPage = findDescriptionPage( e.element.id );
			if ( !descriptionPage ) {
				return;
			}

			window.open( this.createDescriptionPageLink( descriptionPage, mw.config.get( "wgPageName" ) ), "_blank" );
		} );

		cpdElements.forEach( ( element: CpdElement ): void => {
			if ( !findDescriptionPage( element.id ) ) {
				return;
			}

			const visual = this.elementFactory.getSVGElement( element );
			this.eventBus.fire( "render.shape", { gfx: visual, element } );
		} );
	}

	private createDescriptionPageLink( descriptionPage: string, returnToParam: string = "" ): string {
		if ( returnToParam === "" ) {
			return mw.util.getUrl( descriptionPage );
		}

		return mw.util.getUrl( descriptionPage, { [ mw.config.get( "cpdReturnToQueryParam" ) as string ]: returnToParam } );
	}

	private async onShowXml(): Promise<void> {
		const syntaxHighlightedXml = await this.api.fetchSyntaxHighlightedXml( this.xml );
		this.dom.showXml( syntaxHighlightedXml );
	}

	private handleNotInitializedDiagram( process: string ): void {
		this.dom.disableButtons();

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
		const revision = container.getAttribute( "data-revision" );
		new CpdViewer( process, container, revision ? Number( revision ) : null );
	} );
} );
