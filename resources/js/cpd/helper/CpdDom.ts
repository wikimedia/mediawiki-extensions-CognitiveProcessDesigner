import EventEmitter from "events";
// noinspection ES6UnusedImports
import util from "types-mediawiki/mw/util";
// noinspection ES6UnusedImports
import Title from "types-mediawiki/mw/Title";
import CpdSaveDialog from "./CpdSaveDialog";
import Button from "../oojs-ui/Button";
import ShowXmlButton from "../oojs-ui/ShowXmlButton";
import LinkButton from "../oojs-ui/LinkButton";
import OpenDialogButton from "../oojs-ui/OpenDialogButton";
import CancelButton from "../oojs-ui/CancelButton";
import DiagramPageLinkButton from "../oojs-ui/DiagramPageLinkButton";
import SvgFileLinkButton from "../oojs-ui/SvgFileLinkButton";
import { ChangeLogMessages } from "./CpdChangeLogger";

interface HtmlElement extends HTMLElement {
	hide: () => void;
	show: () => void;
	addClass: ( className: string ) => void;
	removeClass: ( className: string ) => void;
}

enum ViewMode {
	Modeler,
	Xml,
}

const NARROW_WIDTH = 300;

export default class CpdDom extends EventEmitter {
	private readonly container: HTMLElement;
	private readonly process: string;
	private readonly diagramPage: mw.Title;
	private messageBox: HtmlElement;
	private saveDialog: CpdSaveDialog | undefined;
	private openDialogBtn: Button | undefined;
	private cancelBtn: Button | undefined;
	private showXmlBtn: ShowXmlButton;
	private svgFileLink: LinkButton;
	private diagramPageLink: LinkButton | undefined;
	private canvas: HtmlElement;
	private xmlContainer: HtmlElement;
	private viewMode: ViewMode;
	private isEdit: boolean = false;

	public constructor( container: HTMLElement, process: string, diagramPage: mw.Title ) {
		super();
		this.container = container;
		this.process = process;
		this.diagramPage = diagramPage;
	}

	public onSave( withDescriptionPages: boolean = false ): void {
		this.emit( "save", withDescriptionPages );
	}

	public onCancel(): void {
		this.emit( "cancel" );
	}

	public onEdit(): void {
		this.emit( "edit" );
	}

	public onOpenDialog(): void {
		this.emit( "openDialog" );
		this.saveDialog?.open();
	}

	public toggleView(): void {
		if ( this.viewMode === ViewMode.Modeler ) {
			this.viewMode = ViewMode.Xml;
			this.emit( "showXml" );

			return;
		}

		this.showCanvas();
		this.viewMode = ViewMode.Modeler;
	}

	public showXml( xml: string ): void {
		this.xmlContainer.innerHTML = xml;
		this.xmlContainer.show();
		this.canvas.hide();
		this.showXmlBtn?.setHideLabelAndIcon();
	}

	private showCanvas(): void {
		this.xmlContainer.hide();
		this.canvas.show();
		this.showXmlBtn?.setShowLabelAndIcon();
	}

	public setLoading( loading: boolean ): void {
		if ( loading ) {
			this.saveDialog?.pushPending();
			this.showXmlBtn?.setDisabled( true );
			this.openDialogBtn?.setDisabled( true );
			this.cancelBtn?.setDisabled( true );
		} else {
			this.saveDialog?.popPending();
			this.showXmlBtn?.setDisabled( false );
			this.openDialogBtn?.setDisabled( false );
			this.cancelBtn?.setDisabled( false );
		}
	}

	public getCanvas(): HTMLElement {
		return this.canvas;
	}

	public setSvgLink( svgFile: string | null ): void {
		this.svgFileLink?.setLink( svgFile );
	}

	public disableSvgLink(): void {
		this.svgFileLink?.setDisabled( true );
	}

	public disableShowXmlButton(): void {
		this.showXmlBtn?.setDisabled( true );
	}

	public disableSaveButton( isValid: boolean ): void {
		this.openDialogBtn?.setDisabled( !isValid );
	}

	public setDialogChangelog( messages: ChangeLogMessages ): void {
		this.saveDialog?.setChangelog( messages );
	}

	public showDialogChangesPanel(): void {
		this.saveDialog?.showChanges();
	}

	public showMessage( message: string | null, cls: string = null ): void {
		if ( !message ) {
			return;
		}

		const messageParagraph = document.createElement( "p" );
		messageParagraph.innerHTML = message;
		if ( cls ) {
			messageParagraph.classList.add( cls );
		}

		if ( !this.isEdit ) {
			this.messageBox.append( messageParagraph );
			this.messageBox.show();
			return;
		}

		this.saveDialog?.addPostSaveMessage( messageParagraph );
		this.showDialogChangesPanel();
	}

	public showSuccess( message: string ): void {
		this.showMessage( message );
	}

	public showWarning( message: string ): void {
		this.showMessage( message );
	}

	public showError( message: string ): void {
		this.showMessage( message, "error" );
		this.diagramPageLink?.setDisabled( true );
		this.svgFileLink?.setDisabled( true );
		this.showXmlBtn?.setDisabled( true );
	}

	public initDomElements( isEdit: boolean ): void {
		this.isEdit = isEdit;

		this.messageBox = document.createElement( "div" ) as unknown as HtmlElement;
		this.declareMethods( this.messageBox );
		this.messageBox.addClass( "cpd-message-box" );
		this.messageBox.hide();

		this.canvas = document.createElement( "div" ) as unknown as HtmlElement;
		this.declareMethods( this.canvas );
		this.canvas.addClass( "cpd-canvas" );

		this.xmlContainer = document.createElement( "div" ) as unknown as HtmlElement;
		this.declareMethods( this.xmlContainer );

		const showToolbar = this.container.dataset.toolbar;
		if ( showToolbar ) {
			const toolbar = this.createToolbar();
			this.container.append( toolbar );
		}

		this.container.append(
			this.messageBox,
			this.canvas,
			this.xmlContainer
		);

		this.viewMode = ViewMode.Modeler;
	}

	private createToolbar(): HtmlElement {
		const toolFactory = new OO.ui.ToolFactory();
		const toolbar = new OO.ui.Toolbar( toolFactory, new OO.ui.ToolGroupFactory() );
		const primaryBarButtons = [
			CancelButton.static.name,
			OpenDialogButton.static.name
		];
		const secondaryBarButtons = [
			DiagramPageLinkButton.static.name,
			SvgFileLinkButton.static.name
		];

		toolFactory.register( ShowXmlButton );

		const withDiagramPageLink = mw.config.get( "wgPageName" ) !== this.diagramPage.getPrefixedDb();
		if ( !this.isEdit ) {
			toolFactory.register( SvgFileLinkButton );
			if ( withDiagramPageLink ) {
				toolFactory.register( DiagramPageLinkButton );
			}
			secondaryBarButtons.push( ShowXmlButton.static.name );
		}

		if ( this.isEdit ) {
			this.saveDialog = new CpdSaveDialog();
			this.saveDialog.on( "save", this.onSave.bind( this ) );

			toolFactory.register( OpenDialogButton );
			toolFactory.register( CancelButton );
		}

		toolbar.setup( [
			{
				name: "primary",
				type: "bar",
				include: primaryBarButtons,
				align: "after"
			},
			{
				name: "secondary",
				type: "bar",
				include: secondaryBarButtons
			}
		] );

		[
			...toolbar.getToolGroupByName( "primary" ).getItems(),
			...toolbar.getToolGroupByName( "secondary" ).getItems()
		].forEach( ( item: Button ): void => {
			if ( item.constructor === OpenDialogButton ) {
				this.openDialogBtn = item;
				this.openDialogBtn.onSelect = this.onOpenDialog.bind( this );
				this.openDialogBtn.setDisabled( true );
			}

			if ( item.constructor === CancelButton ) {
				this.cancelBtn = item;
				this.cancelBtn.onSelect = this.onCancel.bind( this );
			}

			if ( item.constructor === ShowXmlButton ) {
				this.showXmlBtn = item;
				this.showXmlBtn.onSelect = this.toggleView.bind( this );
			}

			if ( item.constructor === DiagramPageLinkButton ) {
				this.diagramPageLink = item;
				this.diagramPageLink.setDisabled( true );
				this.diagramPageLink.setLink( this.diagramPage.getUrl( { action: 'edit' } ) );
			}

			if ( item.constructor === SvgFileLinkButton ) {
				this.svgFileLink = item;
				this.svgFileLink.setDisabled( true );
			}
		} );

		toolbar.initialize();

		if ( this.container.clientWidth < NARROW_WIDTH ) {
			toolbar.setNarrow( true );
		} else {
			toolbar.setNarrow( false );
		}

		return toolbar.$element.get( 0 ) as HtmlElement;
	}

	private declareMethods( element: HtmlElement ): void {
		element.hide = () => element.classList.add( "hidden" );
		element.show = () => element.classList.remove( "hidden" );
		element.addClass = ( className: string ) => element.classList.add( className );
		element.removeClass = ( className: string ) => element.classList.remove( className );
	}
}
