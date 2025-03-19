import BpmnModdle from '../../../node_modules/bpmn-moddle/dist';
import { ModdleElement } from "bpmn-js/lib/model/Types";
import NavigatedViewer from "bpmn-js/lib/NavigatedViewer";
import CpdApi from "./helper/CpdApi";
import EventBus from "diagram-js/lib/core/EventBus";
import Canvas from "diagram-js/lib/core/Canvas";

enum SyncState {
	NewToOld,
	OldToNew,
	None
}

export default class CpdBpmnDiffer {
	private static readonly VIEWBOX_CHANGE_END_EVENT: string = "canvas.viewbox.changed";
	private static readonly VIEWBOX_CHANGE_START_EVENT: string = "canvas.viewbox.changing";

	private container: HTMLDivElement;
	private readonly process: string;
	private api: CpdApi;
	private syncState: SyncState

	public constructor( container: HTMLDivElement, process: string, newRevision: number, oldRevision: number ) {
		this.container = container;
		this.process = process;
		this.api = new CpdApi( this.process );
		this.syncState = SyncState.None;

		this.init( newRevision, oldRevision );
	}

	private async init( newRevision: number, oldRevision: number ): Promise<void> {
		const leftContainer = document.createElement( "div" );
		const rightContainer = document.createElement( "div" );
		this.container.appendChild( leftContainer );
		this.container.appendChild( rightContainer );

		try {
			const [ newRevViewer, oldRevViewer ] = await Promise.all( [
				this.initDiagram( newRevision, leftContainer ),
				this.initDiagram( oldRevision, rightContainer ),
			] );

			const newRevCanvas = newRevViewer.get( "canvas" ) as Canvas;
			const oldRevCanvas = oldRevViewer.get( "canvas" ) as Canvas;

			this.enableCanvasSync( newRevViewer.get( "eventBus" ), newRevCanvas, oldRevCanvas, SyncState.NewToOld );
			this.enableCanvasSync( oldRevViewer.get( "eventBus" ), oldRevCanvas, newRevCanvas, SyncState.OldToNew );
		} catch ( error ) {
			console.error( "Error initializing diagrams:", error );
		}
	}

	private async initDiagram( revision: number, container: HTMLDivElement ): Promise<NavigatedViewer> {
		const viewer = new NavigatedViewer();
		const pageContent = await this.api.fetchPageContent( revision );
		await viewer.importXML( pageContent.xml );
		viewer.attachTo( container );

		return viewer;
	}

	private enableCanvasSync( eventBus: EventBus, canvasA: Canvas, canvasB: Canvas, syncDirection: SyncState ): void {
		eventBus.on( CpdBpmnDiffer.VIEWBOX_CHANGE_START_EVENT, () => {
			if ( this.syncState !== syncDirection && this.syncState !== SyncState.None ) {
				return;
			}

			this.syncState = syncDirection;
			canvasB.viewbox( canvasA.viewbox() )
		} );

		eventBus.on( CpdBpmnDiffer.VIEWBOX_CHANGE_END_EVENT, () => {
			this.syncState = SyncState.None;
		} );
	}

	private async loadModel( diagramXML: string ): Promise<ModdleElement> {
		const bpmnModdle = new BpmnModdle();
		const { rootElement } = await bpmnModdle.fromXML( diagramXML );
		return rootElement;
	}
}

new CpdBpmnDiffer(
	document.getElementById( mw.config.get( "cpdDiffContainer" ) as string ) as HTMLDivElement,
	mw.config.get( "cpdProcess" ) as string,
	mw.config.get( "cpdDiffNewRevision" ) as number,
	mw.config.get( "cpdDiffOldRevision" ) as number
);
