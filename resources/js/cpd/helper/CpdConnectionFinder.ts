import { Connection, Element, Shape } from "bpmn-js/lib/model/Types";

export class CpdConnectionFinder {
	private readonly validTypes: Array<string>;

	public constructor(
		validTypes: string[]
	) {
		this.validTypes = validTypes;
	}

	public findIncomingConnections( element: Element, connections: Array<string> ): Array<string> {
		element.incoming.forEach( ( connection: Connection ) => {
			const source = connection.source as Shape;
			if ( this.isValid( source ) ) {
				connections.push( source.id );
			} else {
				this.findIncomingConnections( source, connections );
			}
		} );

		return connections;
	}

	public findOutgoingConnections( element: Element, connections: Array<string> ): Array<string> {
		element.outgoing.forEach( ( connection: Connection ) => {
			const target = connection.target as Shape;
			if ( this.isValid( target ) ) {
				connections.push( target.id );
			} else {
				this.findOutgoingConnections( target, connections );
			}
		} );

		return connections;
	}

	private isValid( shape: Shape ): boolean {
		return this.validTypes.includes( shape.type );
	}
}
