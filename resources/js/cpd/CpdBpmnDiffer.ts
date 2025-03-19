import { diff } from '../../../node_modules/bpmn-js-differ';
import BpmnModdle from '../../../node_modules/bpmn-moddle/dist';
import { ModdleElement } from "bpmn-js/lib/model/Types";
import NavigatedViewer from "bpmn-js/lib/NavigatedViewer";
import CpdApi, { LoadDiagramResult } from "./helper/CpdApi";
import EventBus from "diagram-js/lib/core/EventBus";

export default class CpdBpmnDiffer {

	private container: HTMLDivElement;
	private readonly process: string;
	private api: CpdApi;

	public constructor( containerId: string, process: string, newRevision: number, oldRevision: number ) {
		this.container = document.getElementById( containerId ) as HTMLDivElement;
		this.process = process;
		this.api = new CpdApi( this.process );
		this.initModels( newRevision, oldRevision );
	}

	private async initModels( newRevision: number, oldRevision: number ): Promise<void> {
		const leftContainer = document.createElement( "div" );
		const rightContainer = document.createElement( "div" );
		this.container.appendChild( leftContainer );
		this.container.appendChild( rightContainer );

		const bpmnViewerLeft = new NavigatedViewer();
		let pageContent: LoadDiagramResult = await this.api.fetchPageContent( oldRevision );
		bpmnViewerLeft.attachTo( leftContainer );
		bpmnViewerLeft.importXML( pageContent.xml );
		const bpmnViewerLeftCanvas = bpmnViewerLeft.get( "canvas" );
		const eventBus = bpmnViewerLeft.get( "eventBus" ) as EventBus
		console.log(eventBus)
		eventBus.on('drag.start', function(event) {
			console.log('dragging start');
		});

		eventBus.on('drag.init', function(event) {
			console.log('dragging init');
		});

		eventBus.on('drag.end', function(event) {
			console.log('dragging end');
		});

		const bpmnViewerRight = new NavigatedViewer();
		pageContent = await this.api.fetchPageContent( oldRevision );
		bpmnViewerRight.attachTo( rightContainer );
		bpmnViewerRight.importXML( pageContent.xml );
		const bpmnViewerRightCanvas = bpmnViewerLeft.get( "canvas" );
	}

	private async loadModel( diagramXML: string ): Promise<ModdleElement> {
		const bpmnModdle = new BpmnModdle();
		const { rootElement } = await bpmnModdle.fromXML( diagramXML );
		return rootElement;
	}
}

new CpdBpmnDiffer(
	mw.config.get( "cpdDiffContainer" ) as string,
	mw.config.get( "cpdProcess" ) as string,
	mw.config.get( "cpdDiffNewRevision" ) as number,
	mw.config.get( "cpdDiffOldRevision" ) as number
);
