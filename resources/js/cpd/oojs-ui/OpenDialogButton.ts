import Button from "./Button";

export default class OpenDialogButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "openDialog",
			label: mw.msg( "savechanges-start" ),
			title: mw.msg( "savechanges-start" ),
			flags: [ "primary", "progressive" ]
		}
	};
}
