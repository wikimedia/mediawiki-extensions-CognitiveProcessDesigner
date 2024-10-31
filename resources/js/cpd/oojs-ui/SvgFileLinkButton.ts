import LinkButton from "./LinkButton";

export default class SvgFileLinkButton extends LinkButton {
	public static readonly static = {
		...LinkButton.static, ...{
			name: "svgFileLink",
			label: mw.msg( "cpd-link-svg-title" ),
			title: mw.msg( "cpd-link-svg-title" ),
			icon: "image"
		}
	};

	setDisabled( disabled: boolean ): this {
		if ( disabled ) {
			this.setIcon( "imageLock" );
		} else {
			this.setIcon( "image" );
		}

		return super.setDisabled( disabled );
	}
}
