import Button from "./Button";

export default class CancelButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "cancel",
			label: mw.msg( "cpd-button-cancel-title" ),
			title: mw.msg( "cpd-button-cancel-title" ),
			displayBothIconAndLabel: false,
			icon: "close",
			flags: []
		}
	};
}
