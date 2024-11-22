import { ChangeLogMessages, MessageObject } from "../helper/CpdChangeLogger";

export enum Mode {
	SAVE = "save",
	REVIEW = "review",
	CHANGES = "changes"
}

export default class SaveDialog extends OO.ui.ProcessDialog {
	private panels: OO.ui.StackLayout;
	private savePanel: OO.ui.PanelLayout;
	private reviewPanel: OO.ui.PanelLayout;
	private reviewContent: HTMLDivElement;
	// Displays changes after saving
	private changesPanel: OO.ui.PanelLayout;
	private postSaveMessages: HTMLDivElement;
	private changeLogMessages: ChangeLogMessages;
	private saveWithPages: boolean = false;

	static readonly static = {
		...OO.ui.ProcessDialog.static, ...{
			name: "saveDialog",
			title: mw.msg( "cpd-dialog-save-label-save" ),
			actions: [
				{
					action: "save",
					label: mw.msg( "savechanges" ),
					flags: [ "primary", "progressive" ],
					modes: [ Mode.SAVE, Mode.REVIEW ]
				},
				{
					action: "close",
					label: mw.msg( "cpd-dialog-save-label-resume-editing" ),
					flags: [ "safe", "close" ],
					modes: [ Mode.SAVE, Mode.CHANGES ]
				},
				{
					action: "back",
					label: mw.msg( "cpd-dialog-save-label-resume-editing" ),
					flags: [ "safe", "back" ],
					modes: [ Mode.REVIEW ]
				},
				{
					action: "review",
					label: mw.msg( "cpd-dialog-save-label-review" ),
					modes: [ Mode.SAVE ]
				}
			]
		}
	};

	public initialize(): this {
		super.initialize();

		this.getActions().setMode( Mode.SAVE );
		this.panels = new OO.ui.StackLayout( { scrollable: false } );

		this.savePanel = this.initSavePanel();
		this.reviewPanel = this.initReviewPanel();
		this.changesPanel = this.initChangesPanel();

		this.panels.addItems( [ this.savePanel, this.reviewPanel, this.changesPanel ] );

		// @ts-ignore
		this.$body.append( this.panels.$element );

		return this;
	}

	public withSavePages(): boolean {
		return this.saveWithPages;
	}

	public setMode( mode: Mode ): void {
		const actions = this.getActions();
		actions.setMode( mode );
		this.popPending();
		this.swapPanel( mode );
	}

	public setTitle( title: string ): void {
		// @ts-ignore
		this.title.setLabel( title );
	}

	public pushPending(): this {
		this.getActions().setAbilities( { review: false, save: false } );
		return super.pushPending.call( this );
	}

	public popPending(): this {
		const parent = super.popPending.call( this );
		if ( !this.isPending() ) {
			this.getActions().setAbilities( { review: true, save: true } );
		}
		return parent;
	}

	public getBodyHeight(): number {
		return this.panels.getCurrentItem().$element.height() + 30;
	}

	public getSetupProcess( data: any ): OO.ui.Process {
		data = data || {};
		return super.getSetupProcess.call( this, data ).next( this.onSetup.bind( this ) );
	}

	public setChangelogMessages( messages: ChangeLogMessages ): void {
		this.changeLogMessages = messages;
	}

	public addPostSaveMessage( message: HTMLParagraphElement ): void {
		this.postSaveMessages.append( message );
		this.updateSize();
	}

	public clearPostSaveMessages(): void {
		this.postSaveMessages.innerHTML = "";
		this.updateSize();
	}

	public hasPostSaveErrors(): boolean {
		return this.postSaveMessages.querySelectorAll( ".error" ).length > 0;
	}

	private onSetup(): void {
		this.updateReviewContent();
		this.setMode( Mode.SAVE );
	}

	private swapPanel( panel: Mode ): void {
		this.setTitle( mw.msg( "cpd-dialog-save-label-" + panel ) );

		if ( panel === Mode.SAVE ) {
			this.panels.setItem( this.savePanel );
		}

		if ( panel === Mode.REVIEW ) {
			this.panels.setItem( this.reviewPanel );
		}

		if ( panel === Mode.CHANGES ) {
			this.panels.setItem( this.changesPanel );
		}

		this.setSize( "medium" );
	}

	private initSavePanel(): OO.ui.PanelLayout {
		const panel = new OO.ui.PanelLayout( { padded: true, expanded: false } );

		const savePagesCheckbox = new OO.ui.CheckboxInputWidget( {
			value: "save-with-description-pages"
		} );
		savePagesCheckbox.connect( this, { change: this.onSavePagesCheckboxChange } );
		const fieldLayout = new OO.ui.FieldLayout( savePagesCheckbox, {
			align: "inline",
			label: mw.msg( "cpd-dialog-save-label-description-pages-checkbox" )
		} );

		panel.$element.append( fieldLayout.$element );

		return panel;
	}

	private initReviewPanel(): OO.ui.PanelLayout {
		const panel = new OO.ui.PanelLayout( { padded: true, expanded: false } );

		this.reviewContent = document.createElement( "div" );
		panel.$element.append( this.reviewContent );

		return panel;
	}

	private initChangesPanel(): OO.ui.PanelLayout {
		const panel = new OO.ui.PanelLayout( { padded: true, expanded: false } );

		this.postSaveMessages = document.createElement( "div" );
		panel.$element.append( this.postSaveMessages );

		return panel;
	}

	private updateReviewContent(): void {
		this.reviewContent.innerHTML = "";
		Object.values( this.changeLogMessages ).forEach( ( messages: MessageObject[] ) => {
			messages.filter( ( message: MessageObject ) => {
				if ( this.saveWithPages ) {
					return true;
				}

				return !message.onlyWithPages;
			} ).forEach( ( message: MessageObject ) => {
				this.reviewContent.innerHTML += `<p>${ message.message }</p>`;
			} );
		} );
	}

	private onSavePagesCheckboxChange( selected: boolean ): void {
		this.saveWithPages = selected;
		this.updateReviewContent();
	}
}
