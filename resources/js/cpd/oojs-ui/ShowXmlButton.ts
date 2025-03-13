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
}
