import CpdDom from "./helper/CpdDom";
import CpdXml from "./helper/CpdXml";
import CpdApi, { LoadDiagramResult } from "./helper/CpdApi";
import { CpdElementFactory } from "./helper/CpdElementFactory";
import BaseViewer from "bpmn-js/lib/BaseViewer";
import Canvas from "diagram-js/lib/core/Canvas";

export abstract class CpdTool {
	protected dom: CpdDom;

	protected api: CpdApi;

	protected xmlHelper: CpdXml;

	protected xml: string;

	protected descriptionPages: string[];

	protected elementFactory: CpdElementFactory;

	protected diagramPage: mw.Title;

	protected canvas: Canvas;

	protected constructor( process: string, container: HTMLElement ) {
		if ( !process ) {
			throw new Error( mw.message( "cpd-error-message-missing-config", "process" ).text() );
		}
		if ( !container ) {
			throw new Error( mw.message( "cpd-error-message-missing-config", "container" ).text() );
		}

		const processNamespace = mw.config.get( "cpdProcessNamespace" );
		if ( !processNamespace ) {
			throw new Error( mw.message( "cpd-error-message-missing-config", "cpdProcessNamespace" ).text() );
		}

		this.diagramPage = mw.Title.newFromText( process, processNamespace );

		this.dom = new CpdDom( container, this.diagramPage );
		this.dom.on( "centerViewport", this.centerViewport.bind( this ) );
		this.xmlHelper = new CpdXml();

		this.api = new CpdApi( process );
		this.api.on( CpdApi.STATUS_REQUEST_STARTED, this.requestStarted.bind( this ) );
		this.api.on( CpdApi.STATUS_REQUEST_FINISHED, this.requestFinished.bind( this ) );
		this.api.on( CpdApi.STATUS_REQUEST_FAILED, this.requestFailed.bind( this ) );
	}

	protected async initPageContent( revision: number | null = null ): Promise<void> {
		const pageContent: LoadDiagramResult = await this.api.fetchPageContent( revision );
		this.xml = pageContent.xml;
		this.dom.setSvgLink( pageContent.svgFile );
		this.descriptionPages = pageContent.descriptionPages;
	}

	// eslint-disable-next-line no-unused-vars
	protected abstract renderDiagram( diagramXml: string, revision: number | null ): Promise<void>;

	protected async attachToCanvas( diagram: BaseViewer ): Promise<void> {
		diagram.attachTo( this.dom.getCanvas() );

		try {
			await diagram.importXML( this.xml );
		} catch ( e ) {
			this.dom.showError( e );
		}

		this.canvas = diagram.get( "canvas" ) as Canvas;
		this.centerViewport();
	}

	protected centerViewport(): void {
		this.canvas.zoom( "fit-viewport" );
	}

	protected throwError( message: string ): void {
		this.dom.showError( message );
		throw new Error( message );
	}

	private requestStarted(): void {
		this.dom.setLoading( true );
	}

	private requestFinished( message: string = null ): void {
		if ( message ) {
			this.dom.showSuccess( message );
		}
		this.dom.setLoading( false );
	}

	private requestFailed( message: string ): void {
		this.dom.setLoading( false );
		this.throwError( message );
	}
}
