import Button from "./Button";

export default class ShowDiagramButton extends Button {
	public static readonly static = {
		...Button.static, ...{
			name: "showDiagram",
			label: mw.msg( 'cpd-button-show-diagram' ),
			title: mw.msg( 'cpd-button-show-diagram' ),
			icon: "imageLayoutFrame"
		}
	};
}
