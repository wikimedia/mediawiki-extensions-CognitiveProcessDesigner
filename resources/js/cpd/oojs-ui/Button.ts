
export default abstract class Button extends OO.ui.Tool {
	static readonly static = {
		...OO.ui.Tool.static, ...{
			displayBothIconAndLabel: true
		}
	};


	onUpdateState() {
	}

	onSelect() {
	}

	setLabel( label: string ) {
		this.setTitle( label );
		this.setDisplayBothIconAndLabel( false );
	}

	getTagName(): string {
		return "span";
	}
}
