export default class CpdXml {
	private domParser: DOMParser;

	public constructor() {
		this.domParser = new DOMParser();
	}

	public validate( xml: string | null ): void {
		let isValid = false;
		if ( xml ) {
			const dom = this.domParser.parseFromString( xml, "text/xml" );
			isValid = dom.documentElement.nodeName !== "parsererror";
		}

		if ( !isValid ) {
			throw new Error( mw.message( "cpd-error-message-invalid-xml" ).text() );
		}
	}
}
