// eslint-disable-next-line no-unused-vars
import notify from "types-mediawiki/mw/notification";

export default class CpdXml {

	public static validate( xml: string | null ): void {
		const domParser = new DOMParser();
		let isValid = false;
		if ( xml ) {
			const dom = domParser.parseFromString( xml, "text/xml" );
			isValid = dom.documentElement.nodeName !== "parsererror";
		}

		if ( !isValid ) {
			throw new Error( mw.message( "cpd-error-message-invalid-xml" ).text() );
		}
	}

	/**
	 * Extension SyntaxHighlight creates a <pre> element with the class mw-highlight
	 * Append a copy button to it
	 *
	 * @param xml
	 */
	public static insertPreToClipButton( xml: string ): void {
		const xmlHighlightElement = document.querySelector( ".mw-highlight" );

		if ( !xmlHighlightElement ) {
			return;
		}

		const preElement = xmlHighlightElement.querySelector( "pre" );

		if ( !preElement.getAttribute( 'tabindex' ) ) {
			preElement.setAttribute( 'tabindex', '0' );
		}

		const copyButton = new OO.ui.ButtonWidget( {
			icon: 'copy',
			classes: [ 'pretoclip-copy-button' ],
			title: mw.message( 'cpd-pretoclip-button-tooltip' ).text()
		} );

		copyButton.on( 'click', function () {
			navigator.clipboard.writeText( xml );
			mw.notify( mw.message( 'cpd-pretoclip-button-notification-text' ).text() );
		}, [], copyButton );

		preElement.prepend( copyButton.$element[ 0 ] );
	}
}
