import ImportButton from "./ImportButton";
import ExportButton from "./ExportButton";

export default class GroupTool extends OO.ui.ToolGroupTool {
	static readonly static = {
		...OO.ui.ToolGroupTool.static, ...{
			name: 'groupTool',
			groupConfig: {
				icon: 'folderPlaceholder',
				include: [
					ExportButton.static.name,
					ImportButton.static.name
				]
			}
		}
	};
}
