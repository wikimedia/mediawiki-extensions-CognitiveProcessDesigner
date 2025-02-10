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
import CenterViewportButton from "../oojs-ui/CenterViewportButton";
import { MessageType } from "../oojs-ui/SaveDialog";
import ToolGroupSetupMap = OO.ui.Toolbar.ToolGroupSetupMap;
import ShowDiagramButton from "../oojs-ui/ShowDiagramButton";

interface HtmlElement extends HTMLElement {
	hide: () => void;
	show: () => void;
	addClass: ( className: string ) => void;
	removeClass: ( className: string ) => void;
}

enum ViewModes {
	Modeler,
	Xml,
}

const NARROW_WIDTH = 300;

export default class CpdDom extends EventEmitter {
	private readonly container: HTMLElement;

	private readonly diagramPage: mw.Title;

	private messageBox: HtmlElement;

	private saveDialog: CpdSaveDialog | undefined;

	private openDialogBtn: Button | undefined;

	private cancelBtn: Button | undefined;

	private showXmlBtn: ShowXmlButton;

	private showDiagramBtn: ShowDiagramButton;

	private centerViewportBtn: CenterViewportButton;

	private svgFileLink: LinkButton;

	private diagramPageLink: LinkButton | undefined;

	private canvas: HtmlElement;

	private xmlContainer: HtmlElement;

	private viewMode: ViewModes;

	private isEdit: boolean = false;

	public constructor( container: HTMLElement, diagramPage: mw.Title ) {
		super();
		this.container = container;
		this.diagramPage = diagramPage;
	}

	public onSave( withDescriptionPages: boolean = false ): void {
		this.emit( "save", withDescriptionPages );
	}

	public onSaveDone(): void {
		this.emit( "saveDone" );
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
		if ( this.viewMode === ViewModes.Modeler ) {
			this.viewMode = ViewModes.Xml;
			this.emit( "showXml" );

			return;
		}

		this.showCanvas();
		this.viewMode = ViewModes.Modeler;
	}

	public showXml( xml: string ): void {
		this.xmlContainer.innerHTML = xml;
		this.xmlContainer.show();
		this.canvas.hide();
		this.showXmlBtn?.setActive( true );
		this.showDiagramBtn?.setActive( false );
		this.centerViewportBtn?.setDisabled( true );
	}

	private showCanvas(): void {
		this.xmlContainer.hide();
		this.canvas.show();
		this.showXmlBtn?.setActive( false );
		this.showDiagramBtn?.setActive( true );
		this.centerViewportBtn?.setDisabled( false );
	}

	private centerViewport(): void {
		this.emit( "centerViewport" );
	}

	public setLoading( loading: boolean ): void {
		if ( loading ) {
			this.saveDialog?.pushPending();
			this.disableButtons();
		} else {
			this.saveDialog?.popPending();
			this.showXmlBtn?.setDisabled( false );
			this.showDiagramBtn?.setDisabled( false );
			this.showDiagramBtn?.setActive( true );
			this.centerViewportBtn?.setDisabled( false );
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

	public disableButtons(): void {
		this.openDialogBtn?.setDisabled( true );
		this.cancelBtn?.setDisabled( true );
		this.showXmlBtn?.setDisabled( true );
		this.showDiagramBtn?.setDisabled( true );
		this.centerViewportBtn?.setDisabled( true );
	}

	public disableSaveButton( isValid: boolean ): void {
		this.openDialogBtn?.setDisabled( !isValid );
	}

	public setDialogChangelog( messages: ChangeLogMessages ): void {
		this.saveDialog?.setChangelog( messages );
	}

	public showDialogChangesPanel(): void {
		const saveDialog = this.saveDialog;

		if ( !saveDialog ) {
			return;
		}

		if ( !saveDialog.hasChanges() ) {
			this.saveDialog.close();

			return;
		}

		saveDialog.showChanges();
	}

	public showMessage( message: HTMLDivElement | string | null, type: MessageType = MessageType.MESSAGE ): void {
		if ( !message ) {
			return;
		}

		if ( typeof message === "string" ) {
			const messageDiv = document.createElement( "div" );
			messageDiv.innerHTML = message;
			message = messageDiv;
		}

		if ( this.isEdit ) {
			if ( this.saveDialog?.isOpened() ) {
				this.saveDialog?.addPostSaveMessage( message, type );
				this.showDialogChangesPanel();

				return;
			}
		}

		this.messageBox.append( message );

		if ( !this.messageBox.classList.contains( type ) ) {
			this.messageBox.addClass( type );
		}

		this.messageBox.show();
	}

	public showSuccess( message: string ): void {
		this.showMessage( message, MessageType.MESSAGE );
	}

	public showWarning( message: string ): void {
		this.showMessage( message, MessageType.WARNING );
	}

	public showError( message: string ): void {
		this.diagramPageLink?.setDisabled( true );
		this.disableButtons();

		if ( this.isEdit ) {
			if ( this.saveDialog?.isOpened() ) {
				this.saveDialog?.addError( message );

				return;
			}
		}

		this.messageBox.append( message );
		this.messageBox.addClass( 'error' );
		this.messageBox.show();
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

		// Reset container if it was already initialized
		this.container.textContent = "";
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

		this.viewMode = ViewModes.Modeler;
	}

	private createToolbar(): HtmlElement {
		const toolFactory = new OO.ui.ToolFactory();
		const toolGroupFactory = new OO.ui.ToolGroupFactory();

		const toolbar = new OO.ui.Toolbar( toolFactory, toolGroupFactory );

		const primaryBarButtons = [
			OpenDialogButton.static.name
		];
		const secondaryBarButtons = [
			CancelButton.static.name,
			DiagramPageLinkButton.static.name,
			SvgFileLinkButton.static.name,
			CenterViewportButton.static.name
		];

		toolFactory.register( ShowXmlButton );
		toolFactory.register( CenterViewportButton );
		toolFactory.register( ShowDiagramButton );

		const withDiagramPageLink = mw.config.get( "wgPageName" ) !== this.diagramPage.getPrefixedDb();
		if ( !this.isEdit ) {
			toolFactory.register( SvgFileLinkButton );
			if ( withDiagramPageLink ) {
				toolFactory.register( DiagramPageLinkButton );
			}
			secondaryBarButtons.push( ShowXmlButton.static.name );
			secondaryBarButtons.push( ShowDiagramButton.static.name );
		}

		const primaryBarConfig = {
			name: "primary",
			type: "bar",
			include: primaryBarButtons,
			align: "after"
		} as ToolGroupSetupMap;

		const secondaryBarConfig = {
			name: "secondary",
			type: "list",
			icon: "ellipsis",
			align: "after",
			include: secondaryBarButtons
		} as ToolGroupSetupMap;

		if ( this.isEdit ) {
			this.saveDialog = new CpdSaveDialog();
			this.saveDialog.on( "save", this.onSave.bind( this ) );
			this.saveDialog.on( "saveDone", this.onSaveDone.bind( this ) );

			toolFactory.register( OpenDialogButton );
			toolFactory.register( CancelButton );

			secondaryBarConfig.type = "bar";
			secondaryBarConfig.align = "before";
		}

		toolbar.setup( [ primaryBarConfig, secondaryBarConfig ] );

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

			if ( item.constructor === ShowDiagramButton ) {
				this.showDiagramBtn = item;
				this.showDiagramBtn.onSelect = this.toggleView.bind( this );
			}

			if ( item.constructor === CenterViewportButton ) {
				this.centerViewportBtn = item;
				this.centerViewportBtn.onSelect = this.centerViewport.bind( this );
			}

			if ( item.constructor === DiagramPageLinkButton ) {
				this.diagramPageLink = item;
				this.diagramPageLink.setLink( this.diagramPage.getUrl( {} ) );
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
