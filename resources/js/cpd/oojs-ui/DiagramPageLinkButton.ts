import LinkButton from "./LinkButton";

export default class DiagramPageLinkButton extends LinkButton {
	public static readonly static = {
		...LinkButton.static, ...{
			name: "diagramPageLink",
			label: mw.msg( "cpd-link-diagram-page-title" ),
			title: mw.msg( "cpd-link-diagram-page-title" ),
			icon: "sandbox",
		},
	};

	setActive() {
	}
}
