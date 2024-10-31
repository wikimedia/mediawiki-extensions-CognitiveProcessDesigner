import Button from "./Button";

export default abstract class LinkButton extends Button {
	setLink( path: string ): void {
		if ( !path ) {
			this.setDisabled( true );
			return;
		}

		if ( path.length === 0 ) {
			this.setDisabled( true );
		} else {
			this.$link.attr( "href", path );
			this.setDisabled( false );
		}
	}
}
