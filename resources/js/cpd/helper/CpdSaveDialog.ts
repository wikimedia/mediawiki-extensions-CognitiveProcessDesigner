import EventEmitter from "events";
import SaveDialog, { Mode, MessageType } from "../oojs-ui/SaveDialog";
import { ChangeLogMessages } from "./CpdChangeLogger";

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

	public open(): void {
		this.dialog.open();
	}

	public showChanges(): void {
		this.dialog.setMode( Mode.CHANGES );

		if ( this.dialog.hasPostSaveErrors() ) {
			this.dialog.setTitle( mw.msg( "cpd-dialog-save-label-changes-with-error" ) );
		}
	}

	public hasChanges(): boolean {
		return this.dialog.hasPostSaveMessages();
	}

	public addPostSaveMessage( message: HTMLDivElement | string, type: MessageType ): void {
		this.dialog.addPostSaveMessage( message, type );
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

	private onReview(): void {
		this.dialog.setMode( Mode.REVIEW );
	}

	private onBack(): void {
		this.dialog.setMode( Mode.SAVE );
	}
}
