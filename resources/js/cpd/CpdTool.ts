import CpdDom from "./helper/CpdDom";
import CpdXml from "./helper/CpdXml";
import CpdApi, { LoadDiagramResult } from "./helper/CpdApi";
import { CpdElementFactory } from "./helper/CpdElementFactory";

export interface ExistingDescriptionPage {
	dbKey: string;
	isNew: boolean;
}

export abstract class CpdTool {
	protected dom: CpdDom;
	protected api: CpdApi;
	protected xmlHelper: CpdXml;
	protected xml: string;
	protected descriptionPages: ExistingDescriptionPage[];
	protected elementFactory: CpdElementFactory;
	protected diagramPage: mw.Title;

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
		this.xmlHelper = new CpdXml();

		this.api = new CpdApi( process );
		this.api.on( CpdApi.STATUS_REQUEST_STARTED, this.requestStarted.bind( this ) );
		this.api.on( CpdApi.STATUS_REQUEST_FINISHED, this.requestFinished.bind( this ) );
		this.api.on( CpdApi.STATUS_REQUEST_FAILED, this.requestFailed.bind( this ) );
	}

	protected async initPageContent(): Promise<void> {
		const pageContent: LoadDiagramResult = await this.api.fetchPageContent();
		this.xml = pageContent.xml;
		this.dom.setSvgLink( pageContent.svgFile );
		this.setDescriptionPages( pageContent.descriptionPages );
	}

	protected abstract renderDiagram( diagramXml: string ): Promise<void>;

	protected throwError( message: string ): void {
		this.dom.showError( message );
		throw new Error( message );
	}

	private setDescriptionPages( descriptionPages: { edited: string[], new: string[] } ): void {
		this.descriptionPages = [
			...descriptionPages.edited?.map( ( dbKey: string ): ExistingDescriptionPage => ( {
				dbKey,
				isNew: false
			} ) ),
			...descriptionPages.new?.map( ( dbKey: string ): ExistingDescriptionPage => ( {
				dbKey,
				isNew: true
			} ) )
		];
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
