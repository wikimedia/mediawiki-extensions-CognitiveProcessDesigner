import CpdXml from "../../resources/js/cpd/helper/CpdXml";

describe( "CpdXml", () => {
	const cpdXml = new CpdXml();
	test.each( [
		[ "<root><element id=\"Flow_0sg2lgc_di\">Text 1</element><element id=\"Activity_1djhtuw_di\">Text 2</element></root>", true ],
		[ "<root><element id=\"Flow_0sg2lgc_di\">Text 1</element><element id=\"Activity_1djhtuw_di\">Text 2", false ],
		[ "", false ]
	] )( "Validate xml", ( xml: string, expectThrow: boolean ) => {
		if ( expectThrow ) {
			expect( () => cpdXml.validate(xml) ).toBeTruthy();
		} else {
			// Message is empty, because it is not possible to get the message from mw.message
			expect( () => cpdXml.validate(xml) ).toThrow( new Error( mw.message( "cpd-error-message-invalid-xml" ).text() ) );
		}
	} );
} );
