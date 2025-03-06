import BpmnModeler from "bpmn-js/lib/Modeler";
import BpmnColorPickerModule from "../../../node_modules/bpmn-js-color-picker/colors/index";
import { SaveSVGResult } from "bpmn-js/lib/BaseViewer";
import { SaveDiagramResult } from "./helper/CpdApi";
import { CpdTool } from "./CpdTool";
import CpdElement from "./model/CpdElement";
import CpdChangeLogger from "./helper/CpdChangeLogger";
import { CpdElementFactory } from "./helper/CpdElementFactory";
import CpdValidator from "./helper/CpdValidator";
import CpdInlineSvgRenderer from "./helper/CpdInlineSvgRenderer";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";
import bpmnlintConfig from "../../../bpmn-lint.config";
import lintModule from 'bpmn-js-bpmnlint';
import EventBus from "diagram-js/lib/core/EventBus";
import { MessageType } from "./oojs-ui/SaveDialog";
import CpdTranslator from "./helper/CpdTranslator";
import CustomPaletteProvider from "./CustomPaletteProvider";

class CpdModeler extends CpdTool {
	private readonly bpmnModeler: BpmnModeler;

	private changeLogger: CpdChangeLogger;

	private initialElements: CpdElement[] = [];

	public constructor( process: string, container: HTMLElement ) {
		super( process, container );

		const translator = new CpdTranslator( mw.config.get( "wgUserLanguage" ) );

		this.bpmnModeler = new BpmnModeler( {
			linting: {
				bpmnlint: bpmnlintConfig
			},
			additionalModules: [
				BpmnColorPickerModule,
				lintModule,
				{
					translate: [ 'value', translator.translate.bind( translator ) ]
				},
				{
					__init__: ["paletteProvider"],
					paletteProvider: ["type", CustomPaletteProvider]
				},
			]
		} );

		this.dom.initDomElements( true );
		this.dom.on( "save", this.onSave.bind( this ) );
		this.dom.on( "saveDone", this.onSaveDone.bind( this ) );
		this.dom.on( "cancel", this.onCancel.bind( this ) );
		this.dom.on( "openDialog", this.onOpenDialog.bind( this ) );

		this.renderDiagram();
	}

	protected async renderDiagram(): Promise<void> {
		const elements = await this.initPageContent();

		const elementRegistry = this.bpmnModeler.get( "elementRegistry" ) as ElementRegistry;
		const svgRenderer = new CpdInlineSvgRenderer( elementRegistry );
		const eventBus = this.bpmnModeler.get( "eventBus" ) as EventBus;

		this.elementFactory = new CpdElementFactory(
			elementRegistry,
			this.descriptionPages
		);
		this.changeLogger = new CpdChangeLogger( eventBus, this.elementFactory, svgRenderer );
		const validator = new CpdValidator( eventBus, elementRegistry );
		validator.on( CpdValidator.VALIDATION_EVENT, this.onValidation.bind( this ) );

		if ( !this.xml ) {
			try {
				await this.createDiagram();
			} catch ( e ) {
				this.dom.showError( e );
			}

			return;
		}

		await this.attachToCanvas( this.bpmnModeler );
		await this.initDescriptionPageElements();
	}

	private async initDescriptionPageElements(): Promise<void> {
		this.initialElements = this.elementFactory.findElementsWithExistingDescriptionPage();
	}

	public async createDiagram(): Promise<void> {
		this.bpmnModeler.attachTo( this.dom.getCanvas() );
		await this.bpmnModeler.createDiagram();
	}

	public async getUpdatedXml(): Promise<string> {
		const saveXmlResult = await this.bpmnModeler.saveXML();

		if ( saveXmlResult.error || !saveXmlResult.xml ) {
			throw new Error( mw.message(
				"cpd-error-message-saving-diagram",
				saveXmlResult.error ).text()
			);
		}

		const xml = saveXmlResult.xml;
		this.xmlHelper.validate( xml );
		return xml;
	}

	public async getSVG(): Promise<SaveSVGResult> {
		return this.bpmnModeler.saveSVG();
	}

	private onValidation( isValid: boolean ): void {
		this.dom.disableSaveButton( isValid );
	}

	private onCancel(): void {
		window.open( mw.util.getUrl( mw.config.get( "wgPageName" ) ), "_self" );
	}

	private async onSave( withPages: boolean ): Promise<void> {
		this.xml = await this.getUpdatedXml();
		const svgResult = await this.getSVG();

		const result = await this.api.saveDiagram(
			this.xml,
			svgResult,
			withPages
		);

		// Reload the page in view mode
		if ( !withPages ) {
			this.reloadInViewMode();

			return;
		}

		this.showAfterSaveMessages( result );
		this.changeLogger.reset();
		this.dom.showDialogChangesPanel();
		this.elementFactory.setExistingDescriptionPages( result.descriptionPages );

		await this.initDescriptionPageElements();
	}

	private onSaveDone(): void {
		this.reloadInViewMode();
	}

	private reloadInViewMode(): void {
		window.open( mw.util.getUrl( mw.config.get( "wgPageName" ) ), "_self" );
	}

	private showAfterSaveMessages( result: SaveDiagramResult ): void {
		result.saveWarnings.forEach( ( warning: string ): void => {
			this.dom.showWarning( warning );
		} );

		if ( result.descriptionPages.length === 0 ) {
			return;
		}

		const messageDiv = document.createElement( "div" );
		const list = document.createElement( "ul" );
		messageDiv.appendChild( list );

		result.descriptionPages.forEach( ( descriptionPage: string ): void => {
			const listItem = document.createElement( "li" );
			listItem.innerHTML = this.changeLogger.createLinkFromDbKey( descriptionPage );
			list.appendChild( listItem );
		} );

		this.dom.showMessage( messageDiv.innerHTML, MessageType.MESSAGE );
	}

	private onOpenDialog(): void {
		const descriptionPageElements = this.elementFactory.createDescriptionPageEligibleElements();
		this.applyDescriptionPageChanges( descriptionPageElements );
		this.dom.setDialogChangelog( this.changeLogger.getMessages() );
	}

	private applyDescriptionPageChanges( elements: CpdElement[] ): void {
		elements.forEach( ( element: CpdElement ): void => {
			if ( !element.descriptionPage ) {
				this.throwError( mw.message( "cpd-error-message-missing-description-page", element.id ).text() );
			}

			const initialElement = this.initialElements.find(
				( el: CpdElement ): boolean => el.id === element.id
			);
			if ( !initialElement ) {
				this.changeLogger.addDescriptionPageChange( element );
				return;
			}

			if ( initialElement.label !== element.label ) {
				this.changeLogger.addDescriptionPageChange( element );
			}
		} );
	}
}

new CpdModeler( mw.config.get( "cpdProcess" ) as string, document.querySelector( "[data-process]" ) );
