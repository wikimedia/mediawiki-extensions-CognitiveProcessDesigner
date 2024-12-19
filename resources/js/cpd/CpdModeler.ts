import BpmnModeler from "bpmn-js/lib/Modeler";
import BpmnColorPickerModule from "../../../node_modules/bpmn-js-color-picker/colors/index";
import { SaveSVGResult } from "bpmn-js/lib/BaseViewer";
import { ElementDescriptionPage } from "./helper/CpdApi";
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

class CpdModeler extends CpdTool {
	private bpmnModeler: BpmnModeler;
	private changeLogger: CpdChangeLogger;
	private initialElements: CpdElement[] = [];

	public constructor( process: string, container: HTMLElement ) {
		super( process, container );

		this.bpmnModeler = new BpmnModeler( {
			linting: {
				bpmnlint: bpmnlintConfig
			},
			additionalModules: [
				BpmnColorPickerModule,
				lintModule
			],
			keyboard: {
				bindTo: window
			}
		} );

		this.dom.initDomElements( true );
		this.dom.on( "save", this.onSave.bind( this ) );
		this.dom.on( "cancel", this.onCancel.bind( this ) );
		this.dom.on( "openDialog", this.onOpenDialog.bind( this ) );

		this.renderDiagram( process );
	}

	protected async renderDiagram( process: string ): Promise<void> {
		await this.initPageContent();

		const elementRegistry = this.bpmnModeler.get( "elementRegistry" ) as ElementRegistry;
		const svgRenderer = new CpdInlineSvgRenderer( elementRegistry );
		const eventBus = this.bpmnModeler.get( "eventBus" ) as EventBus;

		this.elementFactory = new CpdElementFactory( elementRegistry, process, this.descriptionPages );
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
		this.changeLogger.addCreation( this.elementFactory.findInitialElement() );
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
		if ( withPages ) {
			await this.updateElementDescriptionPages();
		}

		this.xml = await this.getUpdatedXml();
		const svgResult = await this.getSVG();
		await this.api.saveDiagram(
			this.xml,
			svgResult
		);
		this.changeLogger.reset();
		this.dom.showDialogChangesPanel();
	}

	private async updateElementDescriptionPages(): Promise<void> {
		const elements = this.elementFactory.createDescriptionPageEligibleElements();
		this.applyDescriptionPageChanges( elements );

		const result = await this.api.saveDescriptionPages( elements );
		result.warnings.forEach( ( warning: string ): void => {
			this.dom.showWarning( warning );
		} );

		if ( result.descriptionPages.length === 0 ) {
			return;
		}

		const descriptionPages = result.descriptionPages.map( ( descriptionPage: string ): ElementDescriptionPage => JSON.parse( descriptionPage ) );
		descriptionPages.forEach( ( descriptionPage: ElementDescriptionPage ): void => {
			const element: CpdElement | undefined = elements
				.find( ( element: CpdElement ): boolean => element.id === descriptionPage.elementId );

			if ( !element ) {
				this.throwError( mw.message( "cpd-error-message-missing-element-link", descriptionPage.elementId ).text() );
			}

			if ( element.descriptionPage?.dbKey !== descriptionPage.page ) {
				this.throwError( mw.message( "cpd-error-message-mismatched-element-link", descriptionPage.elementId ).text() );
			}

			const title = mw.Title.newFromText( descriptionPage.page );
			const linkText = title.getNameText().split( '/' ).pop();
			const link = `<a href="${ mw.util.getUrl( title.getPrefixedText() ) }" target="_blank">${ linkText }</a>`;
			this.dom.showMessage( mw.message( "cpd-description-page-saved-message", link ).text() );
		} );
	}

	private onOpenDialog(): void {
		const descriptionPageElements = this.elementFactory.createDescriptionPageEligibleElements();

		this.applyDescriptionPageChanges( descriptionPageElements );
		this.dom.setDialogChangelog( this.changeLogger.getMessages() );
	}

	private applyDescriptionPageChanges( elements: CpdElement[] ): void {
		elements.forEach( ( element: CpdElement ): void => {
			if ( !element.descriptionPage ) {
				return;
			}

			const initialElement = this.initialElements.find( ( el: CpdElement ): boolean => el.id === element.id );
			if ( !initialElement ) {
				this.changeLogger.addDescriptionPageChange( element );
				return;
			}

			if ( initialElement.descriptionPage?.dbKey !== element.descriptionPage?.dbKey ) {
				element.descriptionPage.oldDbKey = initialElement.descriptionPage?.dbKey;
				this.changeLogger.addDescriptionPageChange( element );
			}
		} );
	}
}

new CpdModeler( mw.config.get( "cpdProcess" ), document.querySelector( "[data-process]" ) );
