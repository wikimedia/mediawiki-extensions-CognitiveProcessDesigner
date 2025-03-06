import PaletteProvider from "bpmn-js/lib/features/palette/PaletteProvider";

export default class CustomPaletteProvider extends PaletteProvider {

	getPaletteEntries() {
		const originalEntries = super.getPaletteEntries();

		// ERM41167 Remove sub-processes from the palette
		delete originalEntries[ "create.subprocess-expanded" ];

		return originalEntries;
	}
}
