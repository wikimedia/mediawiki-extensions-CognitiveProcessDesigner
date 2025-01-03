import Button from "./Button";

export default class ShowXmlButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "showXml",
			label: mw.msg( "cpd-button-show-xml" ),
			title: mw.msg( "cpd-button-show-xml" ),
			icon: "eye"
		}
	};

	setHideLabelAndIcon() {
		this.setIcon( "eyeClosed" );
		this.setLabel( mw.msg( "cpd-button-hide-xml" ) );
	}

	setShowLabelAndIcon() {
		this.setIcon( "eye" );
		this.setLabel( mw.msg( "cpd-button-show-xml" ) );
	}
}
