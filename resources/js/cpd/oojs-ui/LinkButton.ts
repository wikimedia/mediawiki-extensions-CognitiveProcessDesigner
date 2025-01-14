import Button from "./Button";

export default abstract class LinkButton extends Button {
	setLink( path: string ): void {
		if ( !path || path.length === 0 ) {
			this.setDisabled( true );
			return;
		}

		this.$link.attr( "href", path );
		this.setDisabled( false );
	}
}
