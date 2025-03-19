import CpdDom from "./helper/CpdDom";
import CpdXml from "./helper/CpdXml";
import CpdApi from "./helper/CpdApi";
import { CpdElementFactory } from "./helper/CpdElementFactory";
import BaseViewer from "bpmn-js/lib/BaseViewer";
import Canvas from "diagram-js/lib/core/Canvas";
import Modeler from "bpmn-js/lib/Modeler";

export abstract class CpdTool {
	protected bpmnTool: BaseViewer | Modeler;

	protected dom: CpdDom;

	protected api: CpdApi;

	protected xmlHelper: CpdXml;

	protected xml: string;

	protected elementFactory: CpdElementFactory;

	protected diagramPage: mw.Title;

	protected canvas: Canvas;

	protected constructor( process: string, container: HTMLElement, bpmnTool: BaseViewer ) {
		if ( !process ) {
			throw new Error( mw.message( "cpd-error-message-missing-config", "process" ).text() );
		}
		if ( !container ) {
			throw new Error( mw.message( "cpd-error-message-missing-config", "container" ).text() );
		}

		const processNamespace = mw.config.get( "cpdProcessNamespace" ) as number;
		if ( !processNamespace ) {
			throw new Error( mw.message( "cpd-error-message-missing-config", "cpdProcessNamespace" ).text() );
		}

		this.bpmnTool = bpmnTool;
		this.diagramPage = mw.Title.newFromText( process, processNamespace );

		this.dom = new CpdDom( container, this.diagramPage );
		this.dom.on( "centerViewport", this.centerViewport.bind( this ) );
		this.xmlHelper = new CpdXml();

		this.api = new CpdApi( process );
		this.api.on( CpdApi.STATUS_REQUEST_STARTED, this.requestStarted.bind( this ) );
		this.api.on( CpdApi.STATUS_REQUEST_FINISHED, this.requestFinished.bind( this ) );
		this.api.on( CpdApi.STATUS_REQUEST_FAILED, this.requestFailed.bind( this ) );

		this.elementFactory = new CpdElementFactory( this.bpmnTool.get( "elementRegistry" ) );
	}

	public getXml(): string {
		return this.xml;
	}

	protected async attachToCanvas(): Promise<void> {
		this.bpmnTool.attachTo( this.dom.getCanvas() );

		try {
			await this.bpmnTool.importXML( this.xml );
		} catch ( e ) {
			this.dom.showError( e );
		}

		this.canvas = this.bpmnTool.get( "canvas" ) as Canvas;
		this.centerViewport();
	}

	protected centerViewport(): void {
		this.canvas.zoom( "fit-viewport", {
			x: 0,
			y: 0
		} );
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
