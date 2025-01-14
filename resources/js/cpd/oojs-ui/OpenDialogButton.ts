import Button from "./Button";

export default class OpenDialogButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "openDialog",
			label: mw.msg( "cpd-dialog-save-changes-button-title" ),
			title: mw.msg( "cpd-dialog-save-changes-button-title" ),
			flags: [ "primary", "progressive" ]
		}
	};
}
