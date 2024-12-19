import { ChangeLogMessages, MessageObject } from "../helper/CpdChangeLogger";

export enum Mode {
	SAVE = "save",
	REVIEW = "review",
	CHANGES = "changes"
}

export enum MessageType {
	MESSAGE = "message",
	WARNING = "warning",
	ERROR = "error"
}

export default class SaveDialog extends OO.ui.ProcessDialog {
	private panels: OO.ui.StackLayout;
	private savePanel: OO.ui.PanelLayout;
	private reviewPanel: OO.ui.PanelLayout;
	private reviewContent: HTMLUListElement;
	// Displays changes after saving
	private changesPanel: OO.ui.IndexLayout;
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

		this.updateSize();

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

	public addPostSaveMessage( message: HTMLParagraphElement, type: MessageType ): void {
		this.changesPanel.getTabPanel( type ).$element.append( message );
		this.updateSize();
	}

	public clearPostSaveMessages(): void {
		this.changesPanel.getTabPanel( MessageType.MESSAGE ).$element.empty();
		this.changesPanel.getTabPanel( MessageType.WARNING ).$element.empty();
		this.changesPanel.getTabPanel( MessageType.ERROR ).$element.empty();
		this.updateSize();
	}

	public hasPostSaveErrors(): boolean {
		const errorPanel = this.changesPanel.getTabPanel( MessageType.ERROR );
		return errorPanel.$element.children().length > 0;
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

		this.reviewContent = document.createElement( "ul" );
		panel.$element.append( this.reviewContent );

		return panel;
	}

	private initChangesPanel(): OO.ui.IndexLayout {
		const panel = new OO.ui.IndexLayout( {
			expanded: false,
			framed: true
		} );

		const messages = new OO.ui.TabPanelLayout( MessageType.MESSAGE, { label: 'Messages', expanded: true, scrollable: false } );
		const warnings = new OO.ui.TabPanelLayout( MessageType.WARNING, { label: 'Warnings', expanded: true, scrollable: false } );
		const errors = new OO.ui.TabPanelLayout( MessageType.ERROR, { label: 'Errors', expanded: true, scrollable: false } );

		// @ts-ignore Does addTabPanels really require 2 arguments?
		panel.addTabPanels( [ messages, warnings, errors ], 0 );
		panel.connect( this, {
			set: function () {
				console.log( "set" );
				this.updateSize();
			}
		} );
		this.updateSize();
		return panel;
	}

	private updateReviewContent(): void {
		this.reviewContent.innerHTML = "";
		const changeLogMessages = [];
		Object.values( this.changeLogMessages ).forEach( ( messages: MessageObject[] ) => {
			const filtered = messages.filter( ( message: MessageObject ) => {
				if ( this.saveWithPages ) {
					return true;
				}

				return !message.onlyWithPages;
			} );

			// Merge messages
			changeLogMessages.push( ...filtered );
		} );

		if ( changeLogMessages.length === 0 ) {
			this.reviewContent.innerHTML = mw.message( "cpd-log-no-changes" ).plain();
			return;
		}

		changeLogMessages.forEach( ( message: MessageObject ) => {
			this.reviewContent.innerHTML += `<li>${ message.message }</li>`;
		} );
	}

	private onSavePagesCheckboxChange( selected: boolean ): void {
		this.saveWithPages = selected;
		this.updateReviewContent();
	}
}
