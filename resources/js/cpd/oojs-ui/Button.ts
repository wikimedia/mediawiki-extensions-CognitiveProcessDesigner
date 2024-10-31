
export default abstract class Button extends OO.ui.Tool {
	static readonly static = {
		...OO.ui.Tool.static, ...{
			displayBothIconAndLabel: true
		}
	};

	onUpdateState() {
	}

	setLabel( label: string ) {
		this.setTitle( label );
		this.setDisplayBothIconAndLabel( true );
	}

	getTagName(): string {
		return "span";
	}
}
