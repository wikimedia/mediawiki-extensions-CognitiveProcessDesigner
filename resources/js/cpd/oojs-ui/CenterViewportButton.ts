import Button from "./Button";

export default class CenterViewportButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "centerViewport",
			label: mw.msg( "cpd-button-center-viewport" ),
			title: mw.msg( "cpd-button-center-viewport" ),
			icon: "alignCenter",
			displayBothIconAndLabel: false
		}
	};
}
