import Button from "./Button";

export default class ExportButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "export",
			label: mw.msg( "cpd-button-export" ),
			title: mw.msg( "cpd-button-export" ),
			icon: "download",
			displayBothIconAndLabel: false
		}
	};

	setActive() {
	}
}
