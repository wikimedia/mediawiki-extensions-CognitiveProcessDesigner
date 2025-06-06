import ReplaceMenuProvider from "bpmn-js/lib/features/popup-menu/ReplaceMenuProvider";

export default class CustomPaletteProvider extends ReplaceMenuProvider {
	getPopupMenuEntries( target ) {
		const entries = super.getPopupMenuEntries( target );
		delete entries["replace-with-collapsed-ad-hoc-subprocess"];
		delete entries["replace-with-expanded-subprocess"];
		delete entries["replace-with-ad-hoc-subprocess"];
		delete entries["replace-with-collapsed-subprocess"];

		return entries;
	}
}
