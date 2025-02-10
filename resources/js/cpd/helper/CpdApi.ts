// eslint-disable-next-line no-unused-vars
import user from "types-mediawiki/mw/user";
// eslint-disable-next-line no-unused-vars
import message from "types-mediawiki/mw/message";
// eslint-disable-next-line no-unused-vars
import Api from "types-mediawiki/mw/Api";
import EventEmitter from "events";
import { SaveSVGResult } from "bpmn-js/lib/BaseViewer";
import CpdElement from "../model/CpdElement";

export interface SaveDiagramResult {
	svgFile: string;
	diagramPage: string;
	descriptionPages: string[];
	saveWarnings: string[];
}

export interface LoadDiagramResult {
	xml: string | null;
	descriptionPages: string[];
	svgFile: string | null;
	exists: boolean;
	loadWarnings: string[];
}

export interface ElementDescriptionPage {
	elementId: string;
	page: string;
}

export default class CpdApi extends EventEmitter {
	public static readonly STATUS_REQUEST_STARTED: string = "requestStarted";

	public static readonly STATUS_REQUEST_FINISHED: string = "requestFinished";

	public static readonly STATUS_REQUEST_FAILED: string = "requestFailed";

	private readonly process: string;

	private readonly api: mw.Api;

	public constructor( process: string ) {
		super();
		this.api = new mw.Api();
		this.process = process;
	}

	public async fetchPageContent( revision: number | null ): Promise<LoadDiagramResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		const data = {
			action: "cpd-load-diagram",
			process: this.process,
			token: mw.user.tokens.get( "csrfToken" )
		} as { action: string, process: string, token: string, revisionId?: number };

		if ( revision ) {
			data.revisionId = revision;
		}

		return this.api.post( data ).then( ( result: LoadDiagramResult ): LoadDiagramResult => {
			this.emit( CpdApi.STATUS_REQUEST_FINISHED );
			return result;
		} ).fail( ( errorCode: string, error: any ) => {
			this.emit(
				CpdApi.STATUS_REQUEST_FAILED,
				mw.message( "cpd-api-load-diagram-error-message", this.getErrorMessage( errorCode, error ) ).text()
			);
		} );
	}

	public async saveDiagram(
		xml: string,
		svg: SaveSVGResult,
		withDescriptionPages: boolean,
		elements: CpdElement[] = []
	): Promise<SaveDiagramResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		return this.api.post( {
			action: "cpd-save-diagram",
			process: this.process,
			xml: JSON.stringify( xml ),
			svg: JSON.stringify( svg.svg ),
			elements: JSON.stringify( elements ),
			saveDescriptionPages: withDescriptionPages,
			token: mw.user.tokens.get( "csrfToken" )
		} ).then( ( result ): SaveDiagramResult => {
			if ( withDescriptionPages ) {
				this.emit(
					CpdApi.STATUS_REQUEST_FINISHED,
					mw.message( "cpd-api-save-description-pages-success-message", result.descriptionPages.length ).text()
				);
			} else {
				this.emit( CpdApi.STATUS_REQUEST_FINISHED );
			}

			return result as SaveDiagramResult;
		} ).fail( ( errorCode: string, error: any ) => {
			if ( withDescriptionPages ) {
				this.emit(
					CpdApi.STATUS_REQUEST_FAILED,
					mw.message( "cpd-api-save-description-pages-error-message", this.getErrorMessage( errorCode, error ) ).text()
				);
			} else {
				this.emit(
					CpdApi.STATUS_REQUEST_FAILED,
					mw.message( "cpd-api-save-diagram-error-message", this.getErrorMessage( errorCode, error ) ).text()
				);
			}
		} );
	}

	public async fetchSyntaxHighlightedXml( xml: string ): Promise<string> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		return this.api.post( {
			action: "cpd-syntax-highlight-xml",
			xml: JSON.stringify( xml )
		} ).then( ( result: { highlightedXml: string } ) => {
			this.emit( CpdApi.STATUS_REQUEST_FINISHED );
			return result.highlightedXml;
		} ).fail( ( errorCode: string, error: any ) => {
			this.emit( CpdApi.STATUS_REQUEST_FAILED, this.getErrorMessage( errorCode, error ) );
		} );
	}

	private getErrorMessage( errorCode: string, error: any ): string {
		if ( error?.error?.info ) {
			return error.error.info;
		}
		return errorCode === "unknown" ? error.error : errorCode;
	}
}
