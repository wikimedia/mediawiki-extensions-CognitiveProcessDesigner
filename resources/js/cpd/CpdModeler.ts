import BpmnModeler from "bpmn-js/lib/Modeler";
import BpmnColorPickerModule from "../../../node_modules/bpmn-js-color-picker/colors/index";
import { SaveSVGResult } from "bpmn-js/lib/BaseViewer";
import { LoadDiagramResult, SaveDiagramResult } from "./helper/CpdApi";
import { CpdTool } from "./CpdTool";
import CpdChangeLogger from "./helper/CpdChangeLogger";
import CpdValidator from "./helper/CpdValidator";
import CpdInlineSvgRenderer from "./helper/CpdInlineSvgRenderer";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";
import bpmnlintConfig from "../../../bpmn-lint.config";
import lintModule from 'bpmn-js-bpmnlint';
import EventBus from "diagram-js/lib/core/EventBus";
import { MessageType } from "./oojs-ui/SaveDialog";
import CpdTranslator from "./helper/CpdTranslator";
import CustomPaletteProvider from "./CustomPaletteProvider";
import CustomReplaceMenuProvider from "./CustomReplaceMenuProvider";
import CpdLinker from "./helper/CpdLinker";
import CpdXml from "./helper/CpdXml";

class CpdModeler extends CpdTool {
	private changeLogger: CpdChangeLogger;

	public constructor( process: string, container: HTMLElement, enableLinting: boolean = true ) {
		const translator = new CpdTranslator( mw.config.get( "wgUserLanguage" ) );

		const baseAdditionalModules = [
			BpmnColorPickerModule,
			{ translate: ['value', translator.translate.bind(translator)] },
			{
				__init__: ["paletteProvider", "replaceMenuProvider"],
				paletteProvider: ["type", CustomPaletteProvider],
				replaceMenuProvider: ["type", CustomReplaceMenuProvider],
			},
		];

		const modelerOptions = {
			additionalModules: [
				...baseAdditionalModules,
				...(enableLinting ? [lintModule] : [])
			],
			...(enableLinting
				? { linting: { bpmnlint: bpmnlintConfig } }
				: {}),
		};

		const bpmnModeler = new BpmnModeler( modelerOptions );

		super( process, container, bpmnModeler );

		this.dom.initDomElements( true );
		this.dom.on( "save", this.onSave.bind( this ) );
		this.dom.on( "saveDone", this.onSaveDone.bind( this ) );
		this.dom.on( "cancel", this.onCancel.bind( this ) );
		this.dom.on( "openDialog", this.onOpenDialog.bind( this ) );

		const elementRegistry = this.bpmnTool.get( "elementRegistry" ) as ElementRegistry;
		const svgRenderer = new CpdInlineSvgRenderer( elementRegistry );
		const eventBus = this.bpmnTool.get( "eventBus" ) as EventBus;

		this.changeLogger = new CpdChangeLogger( eventBus, this.elementFactory, svgRenderer );
		const validator = new CpdValidator( eventBus );
		validator.on( CpdValidator.VALIDATION_EVENT, this.onValidation.bind( this ) );

		this.renderDiagram();
	}

	private async renderDiagram(): Promise<void> {
		const pageContent: LoadDiagramResult = await this.api.fetchPageContent();

		pageContent.loadWarnings.forEach( ( warning: string ): void => {
			this.dom.showWarning( warning );
		} );

		this.xml = pageContent.xml;

		if ( !this.xml ) {
			try {
				await this.createDiagram();

				return;
			} catch ( e ) {
				this.dom.showError( e );
			}
		}

		await this.attachToCanvas();

		this.dom.setSvgLink( pageContent.svgFile );
		this.dom.setOpenDialogOptions( {
			savePagesCheckboxState: pageContent.descriptionPages.length > 0
		} );
	}

	public async createDiagram(): Promise<void> {
		this.bpmnTool.attachTo( this.dom.getCanvas() );

		// @ts-ignore
		await this.bpmnTool.createDiagram();
	}

	public async getUpdatedXml(): Promise<string> {
		const saveXmlResult = await this.bpmnTool.saveXML();

		if ( saveXmlResult.error || !saveXmlResult.xml ) {
			throw new Error( mw.message(
				"cpd-error-message-saving-diagram",
				saveXmlResult.error ).text()
			);
		}

		CpdXml.validate( saveXmlResult.xml );

		return saveXmlResult.xml;
	}

	public async getSVG(): Promise<SaveSVGResult> {
		return this.bpmnTool.saveSVG();
	}

	private onValidation( isValid: boolean ): void {
		this.dom.disableSaveButton( isValid );
	}

	private onCancel(): void {
		OO.ui.confirm( mw.message( 'cpd-cancel-confirm' ).text() )
			.done( ( confirmed ) => {
				if ( confirmed ) {
					window.open( mw.util.getUrl( mw.config.get( "wgPageName" ) ), "_self" );
				}
			} );
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
			const link = CpdLinker.createLinkFromDbKey( descriptionPage );
			if ( !link ) {
				return;
			}

			const listItem = document.createElement( "li" );
			listItem.innerHTML = link;
			list.appendChild( listItem );
		} );

		this.dom.showMessage( messageDiv.innerHTML, MessageType.MESSAGE );
	}

	private onOpenDialog(): void {
		this.dom.setDialogChangelog( this.changeLogger.getMessages() );
	}
}

new CpdModeler(
	mw.config.get( "cpdProcess" ) as string,
	document.querySelector( "[data-process]" ),
	mw.config.get( "cpdEnableLinting" ) as boolean,
);
