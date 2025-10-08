import Button from "./Button";

export default class ImportButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "import",
			label: mw.msg( "cpd-button-import" ),
			title: mw.msg( "cpd-button-import" ),
			icon: "upload",
			displayBothIconAndLabel: false
		}
	};

	setActive() {
	}
}
