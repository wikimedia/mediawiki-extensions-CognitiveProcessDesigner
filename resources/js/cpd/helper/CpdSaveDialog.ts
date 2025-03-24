import EventEmitter from "events";
import SaveDialog, { MessageType, Mode } from "../oojs-ui/SaveDialog";
import { ChangeLogMessages } from "./CpdChangeLogger";

export interface OpenDialogOptions {
	savePagesCheckboxState: boolean;
}

export default class CpdSaveDialog extends EventEmitter {
	private readonly dialog: SaveDialog;

	public constructor() {
		super();

		this.dialog = new SaveDialog();
		this.dialog.getActionProcess = ( action?: string ): OO.ui.Process => {
			if ( action === "save" ) {
				return new OO.ui.Process( this.onSave.bind( this ) );
			}
			if ( action === "close" ) {
				return new OO.ui.Process( this.close.bind( this ) );
			}
			if ( action === "review" ) {
				return new OO.ui.Process( this.onReview.bind( this ) );
			}
			if ( action === "back" ) {
				return new OO.ui.Process( this.onBack.bind( this ) );
			}
			if ( action === "saveDone" ) {
				return new OO.ui.Process( this.onSaveDone.bind( this ) );
			}

			throw new Error( "Dialog action not implemented" );
		};

		const windowManager = new OO.ui.WindowManager();
		windowManager.addWindows( [ this.dialog ] );
		document.body.appendChild( windowManager.$element.get( 0 ) );
	}

	public close(): void {
		this.dialog.clearPostSaveMessages();
		this.dialog.close();
	}

	public open( options? : OpenDialogOptions ): void {
		this.dialog.open();
		this.dialog.setSavePagesCheckboxState( options?.savePagesCheckboxState ?? false );
	}

	public isOpened(): boolean {
		return this.dialog.isOpened();
	}

	public showChanges(): void {
		this.dialog.setMode( Mode.CHANGES );
	}

	public hasChanges(): boolean {
		return this.dialog.hasPostSaveMessages();
	}

	public addPostSaveMessage( message: string, type: MessageType ): void {
		const messageDiv = document.createElement( "div" );
		messageDiv.innerHTML = message;

		this.dialog.addPostSaveMessage( messageDiv, type );
	}

	public addError( message: string ): void {
		const error = new OO.ui.Error( message as string );

		// @ts-ignore
		this.dialog.showErrors( error );
	}

	public setChangelog( messages: ChangeLogMessages ): void {
		this.dialog.setChangelogMessages( messages );
	}

	public pushPending(): void {
		this.dialog.pushPending();
	}

	public popPending(): void {
		this.dialog.popPending();
	}

	private onSave(): void {
		this.emit( "save", this.dialog.withSavePages() );
	}

	private onSaveDone(): void {
		this.emit( "saveDone" );
	}

	private onReview(): void {
		this.dialog.setMode( Mode.REVIEW );
	}

	private onBack(): void {
		this.dialog.setMode( Mode.SAVE );
	}
}
