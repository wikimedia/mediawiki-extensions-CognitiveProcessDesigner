export default class CpdLinker {
	public static createLinkFromDbKey( dbKey: string | null ): string | null {
		if ( !dbKey ) {
			return null;
		}

		const splitted = dbKey.split( "/" );

		if ( !splitted.shift().includes( ":" ) ) {
			return null;
		}

		const linkText = splitted.join( "/" ).replace( /_/g, " " );
		return `<a target="_blank" href="${ mw.util.getUrl( dbKey ) }">${ linkText }</a>`;
	}
}
