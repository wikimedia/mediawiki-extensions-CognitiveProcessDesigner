export default abstract class Button extends OO.ui.Tool {
	static readonly static = {
		...OO.ui.Tool.static, ...{
			displayBothIconAndLabel: true
		}
	};

	onUpdateState() {
	}

	setActive() {
	}

	setLabel( label: string ) {
		this.setTitle( label );
	}

	getTagName(): string {
		return "span";
	}
}
