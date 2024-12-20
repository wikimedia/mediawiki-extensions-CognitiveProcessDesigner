export default class ErrorsContainer {

	private dismissButton: OO.ui.ButtonWidget;

	private container: HTMLDivElement;

	private errors: HTMLDivElement;

	public constructor( parent: HTMLElement ) {
		this.dismissButton = new OO.ui.ButtonWidget( {
			label: OO.ui.msg( 'ooui-dialog-process-dismiss' )
		} );

		this.dismissButton.connect( this, {
			// @ts-ignore
			click: 'onDismissErrorButtonClick'
		} );

		const errorsTitle = document.createElement( 'div' );
		errorsTitle.classList.add( 'oo-ui-processDialog-errors-title' );
		errorsTitle.textContent = OO.ui.msg( 'ooui-dialog-process-error' );

		this.container = document.createElement( 'div' );
		this.container.classList.add( 'oo-ui-processDialog-errors' );

		const buttonContainer = document.createElement( 'div' );
		buttonContainer.classList.add( 'oo-ui-processDialog-errors-actions' );
		buttonContainer.append( this.dismissButton.$element.get( 0 ) );

		this.errors = document.createElement( 'div' );

		this.container.append(
			errorsTitle,
			this.errors,
			buttonContainer
		);

		this.hide();

		parent.append( this.container );
	}

	public show(): void {
		if ( !this.container.classList.contains( 'oo-ui-element-hidden' ) ) {
			return;
		}

		this.container.classList.remove( 'oo-ui-element-hidden' );
	}

	public hide(): void {
		if ( this.container.classList.contains( 'oo-ui-element-hidden' ) ) {
			return;
		}

		this.container.classList.add( 'oo-ui-element-hidden' );
	}

	private onDismissErrorButtonClick(): void {
		this.clearErrors();
	}

	public addError( message: string ): void {
		const errorWidget = new OO.ui.MessageWidget( { type: 'error' } );
		errorWidget.setLabel( message );
		this.errors.append( errorWidget.$element.get( 0 ) );
		this.show();
	}

	public clearErrors(): void {
		this.errors.innerHTML = '';
		this.hide();
	}

	public hasErrors(): boolean {
		return this.errors.children.length > 0;
	}
}
