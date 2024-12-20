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
}

export interface LoadDiagramResult {
	xml: string | null;
	descriptionPages: {
		new: string[];
		edited: string[];
	};
	svgFile: string | null;
	exists: boolean;
}

export interface SaveDescriptionPagesResult {
	descriptionPages: string[];
	warnings: string[];
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

	public async fetchPageContent(): Promise<LoadDiagramResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		return this.api.post( {
			action: "cpd-load-diagram",
			process: this.process,
			token: mw.user.tokens.get( "csrfToken" )
		} ).then( ( result: LoadDiagramResult ): LoadDiagramResult => {
			this.emit( CpdApi.STATUS_REQUEST_FINISHED );
			return result;
		} );
	}

	public async saveDiagram(
		xml: string,
		svg: SaveSVGResult
	): Promise<SaveDiagramResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		return this.api.post( {
			action: "cpd-save-diagram",
			process: this.process,
			xml: JSON.stringify( xml ),
			svg: JSON.stringify( svg.svg ),
			token: mw.user.tokens.get( "csrfToken" )
		} ).then( ( result ): SaveDiagramResult => {
			this.emit( CpdApi.STATUS_REQUEST_FINISHED );
			return result as SaveDiagramResult;
		} ).fail( ( errorCode: string, error: any ) => {
			this.emit(
				CpdApi.STATUS_REQUEST_FAILED,
				mw.message( "cpd-api-save-diagram-error-message", this.getErrorMessage( errorCode, error ) ).text()
			);
		} );
	}

	public async saveDescriptionPages(
		elements: CpdElement[]
	): Promise<SaveDescriptionPagesResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		return this.api.post( {
			action: "cpd-save-description-pages",
			process: this.process,
			elements: JSON.stringify( elements ),
			token: mw.user.tokens.get( "csrfToken" )
		} ).then( ( result: SaveDescriptionPagesResult ): SaveDescriptionPagesResult => {
			this.emit(
				CpdApi.STATUS_REQUEST_FINISHED,
				mw.message( "cpd-api-save-description-pages-success-message", result.descriptionPages.length ).text()
			);

			return result;
		} ).fail( ( errorCode: string, error: any ) => {
			this.emit(
				CpdApi.STATUS_REQUEST_FAILED,
				mw.message( "cpd-api-save-description-pages-error-message", this.getErrorMessage( errorCode, error ) ).text()
			);
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
