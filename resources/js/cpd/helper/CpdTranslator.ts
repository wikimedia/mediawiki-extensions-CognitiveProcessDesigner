import * as de from "../../../../node_modules/bpmn-js-i18n/translations/de.js";
import * as deCustom from "../translations/de.js";

export default class CpdTranslator {

	private readonly translations: Record<string, any>;

	public constructor( language: string ) {
		switch ( language.toLowerCase() ) {
			case "de":
				this.translations = { ...de.default, ...deCustom.default };
				break;
			default:
				this.translations = null; // Fallback to English
				break;
		}
	}

	public translate( text: string, args: Record<string, any> ): string {
		if ( !this.translations || !this.translations[ text ] ) {
			return this.replaceTemplateStrings( text, args );
		}

		return this.replaceTemplateStrings( this.translations[ text ], args );
	}

	private replaceTemplateStrings( text: string, args: Record<string, any> ): string {
		return text.replace( /\{(\w+)\}/g, ( match, key ) => args[ key ] || match );
	}
}
