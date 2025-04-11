import { diff } from '../../../node_modules/bpmn-js-differ';
import BpmnModdle from '../../../node_modules/bpmn-moddle/dist';
import { ModdleElement } from "bpmn-js/lib/model/Types";
import NavigatedViewer from "bpmn-js/lib/NavigatedViewer";
import CpdApi from "./helper/CpdApi";
import EventBus from "diagram-js/lib/core/EventBus";
import Canvas from "diagram-js/lib/core/Canvas";
import Overlays from "diagram-js/lib/features/overlays/Overlays";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";

enum SyncState {
	NewToOld,
	OldToNew,
	None
}

interface Changes {
	layoutChanged: string[]
	changed: string[]
	removed: string[]
	added: string[]
}

export class CpdBpmnDiffer {
	private static readonly VIEWBOX_CHANGE_END_EVENT: string = "canvas.viewbox.changed";
	private static readonly VIEWBOX_CHANGE_START_EVENT: string = "canvas.viewbox.changing";
	private static readonly DIFF_CONTAINER_ID: string = "cpd-diff-container";

	private readonly process: string;
	private readonly containerHeight: number;
	private api: CpdApi;
	private syncState: SyncState

	public constructor( process: string, containerHeight: number ) {
		this.process = process;
		this.containerHeight = containerHeight;
		this.api = new CpdApi( this.process );
		this.syncState = SyncState.None;
	}

	public async createDiff( newRevision: number, oldRevision: number ): Promise<HTMLDivElement> {
		const container = document.createElement( "div" );
		container.setAttribute( "id", CpdBpmnDiffer.DIFF_CONTAINER_ID );
		container.style.height = `${ this.containerHeight }px`;

		const leftContainer = document.createElement( "div" );
		const rightContainer = document.createElement( "div" );
		container.appendChild( leftContainer );
		container.appendChild( rightContainer );

		const [ newRevViewer, oldRevViewer ] = await Promise.all( [
			this.initViewer( newRevision, leftContainer ),
			this.initViewer( oldRevision, rightContainer ),
		] );

		this.initCanvasSync( newRevViewer.viewer, oldRevViewer.viewer );

		const changes = await this.computeChanges( newRevViewer.xml, oldRevViewer.xml );
		this.addChangeOverlays( changes, newRevViewer.viewer );
		this.addChangeOverlays( changes, oldRevViewer.viewer );

		return container;
	}

	private addChangeOverlays( changes: Changes, viewer: NavigatedViewer ): void {
		const overlays = viewer.get( "overlays" ) as Overlays;
		const elementRegistry = viewer.get( "elementRegistry" ) as ElementRegistry;
		this.applyChanges( changes.added, elementRegistry, overlays, 'added' );
		this.applyChanges( changes.removed, elementRegistry, overlays, 'removed' );
		this.applyChanges( changes.changed, elementRegistry, overlays, 'changed' );
		this.applyChanges( changes.layoutChanged, elementRegistry, overlays, 'layoutChanged' );
	}

	private applyChanges(
		elements: string[],
		elementRegistry: ElementRegistry,
		overlays: Overlays,
		type: string
	): void {
		elements.forEach( ( id: string ) => {
			const shape = elementRegistry.get( id );

			if ( !shape ) {
				return;
			}

			const overlayHtml = document.createElement( "div" );
			overlayHtml.classList.add( "diff-overlay" );
			overlayHtml.classList.add( type );

			overlays.add( id, {
				position: {
					top: 0,
					left: 0
				},
				html: overlayHtml
			} );
		} );
	}

	private async initViewer( revision: number, container: HTMLDivElement ): Promise<{
		viewer: NavigatedViewer;
		xml: string;
	}> {
		const pageContent = await this.api.fetchPageContent( revision );
		const xml = pageContent.xml;
		const viewer = new NavigatedViewer();
		await viewer.importXML( xml );
		viewer.attachTo( container );

		return {
			viewer,
			xml
		};
	}

	private initCanvasSync( newViewer: NavigatedViewer, oldViewer: NavigatedViewer ): void {
		const newRevCanvas = newViewer.get( "canvas" ) as Canvas;
		const oldRevCanvas = oldViewer.get( "canvas" ) as Canvas;

		this.enableCanvasSync( newViewer.get( "eventBus" ), newRevCanvas, oldRevCanvas, SyncState.NewToOld );
		this.enableCanvasSync( oldViewer.get( "eventBus" ), oldRevCanvas, newRevCanvas, SyncState.OldToNew );
	}

	private async computeChanges( newXml: string, oldXml: string ): Promise<Changes> {
		const [ newRootElement, oldRootElement ] = await Promise.all( [
			this.loadBpmnModdle( newXml ),
			this.loadBpmnModdle( oldXml ),
		] );

		const changes = diff( newRootElement, oldRootElement );

		return {
			layoutChanged: Object.keys( changes._layoutChanged ),
			changed: Object.keys( changes._changed ),
			removed: Object.keys( changes._removed ),
			added: Object.keys( changes._added )
		}
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

	private async loadBpmnModdle( diagramXML: string ): Promise<ModdleElement> {
		const bpmnModdle = new BpmnModdle();
		const { rootElement } = await bpmnModdle.fromXML( diagramXML );

		return rootElement;
	}
}
