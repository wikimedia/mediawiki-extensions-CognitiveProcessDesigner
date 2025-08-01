// eslint-disable-next-line no-unused-vars
import user from "types-mediawiki/mw/user";
// eslint-disable-next-line no-unused-vars
import message from "types-mediawiki/mw/message";
// eslint-disable-next-line no-unused-vars
import Api from "types-mediawiki/mw/Api";
import EventEmitter from "events";
import { SaveSVGResult } from "bpmn-js/lib/BaseViewer";
import { CpdElementJson } from "./CpdElementFactory";

export interface SaveDiagramResult {
	diagramPage: string;
	descriptionPages: string[];
	svgFile: string;
	saveWarnings: string[];
}

export interface LoadDiagramResult {
	xml: string | null;
	elements: CpdElementJson[];
	descriptionPages: string[];
	svgFile: string | null;
	revId: number | null;
	loadWarnings: string[];
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

	public async fetchPageContent( revision: number | null = null ): Promise<LoadDiagramResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		const data = {
			action: "cpd-load-diagram",
			process: this.process
		} as { action: string, process: string, token: string, revision?: number };

		if ( revision ) {
			data.revision = revision;
		}

		return this.api.get( data ).then( ( result: any ): LoadDiagramResult => {
			this.emit( CpdApi.STATUS_REQUEST_FINISHED );

			result.elements = result.elements.map( ( element ): CpdElementJson => JSON.parse( element ) );
			result.descriptionPages = this.gatherDescriptionPages( result.elements );

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
		withDescriptionPages: boolean
	): Promise<SaveDiagramResult> {
		this.emit( CpdApi.STATUS_REQUEST_STARTED );

		return this.api.post( {
			action: "cpd-save-diagram",
			process: this.process,
			xml: JSON.stringify( xml ),
			svg: JSON.stringify( svg.svg ),
			savedescriptionpages: withDescriptionPages,
			token: mw.user.tokens.get( "csrfToken" )
		} ).then( ( result: any ): SaveDiagramResult => {
			const elements = result.elements.map( ( element ): CpdElementJson => JSON.parse( element ) );
			result.descriptionPages = this.gatherDescriptionPages( elements );

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

	private gatherDescriptionPages( elements: CpdElementJson[] ): string[] {
		return elements
			.filter( ( element: CpdElementJson ): boolean => {
				return !!element.descriptionPage;
			} )
			.map( ( element: CpdElementJson ): string => {
				return element.descriptionPage;
			} );
	}
}
