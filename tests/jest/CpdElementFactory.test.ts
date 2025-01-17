import { CpdElementFactory } from "../../resources/js/cpd/helper/CpdElementFactory";
import { Connection, Element } from "bpmn-js/lib/model/Types";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";
import EventBus from "diagram-js/lib/core/EventBus";
import CpdElement from "../../resources/js/cpd/model/CpdElement";

describe( "CpdElementFactory", () => {
	const eventBus = new EventBus();

	const descriptionPages = [
		"process/a",
		"process/b"
	];

	test( "createElements", () => {
		const registry = new ElementRegistry( eventBus );
		const factory = new CpdElementFactory( registry, "process", descriptionPages );

		// Description page eligible
		registry.add( createElementFixture( "a", "task" ), createSvgFixture() );
		registry.add( createElementFixture( "b", "event" ), createSvgFixture() );
		registry.add( createElementFixture( "c", "event" ), createSvgFixture() );

		// Not description page eligible
		registry.add( createElementFixture( "d" ), createSvgFixture() );
		registry.add( createElementFixture( "e", "not supported" ), createSvgFixture() );

		const elements = factory.createElements();
		const descriptionPageElements = factory.createDescriptionPageEligibleElements();

		expect( elements ).toHaveLength( 5 );
		expect( descriptionPageElements ).toHaveLength( 3 );

		expect( elements.some( ( element ) => element.id == "e" ) ).toBe( true );
		expect( elements.some( ( element ) => element.id == "d" ) ).toBe( true );
		expect( elements.every( ( element ) => element.descriptionPage ) ).toBe( false );

		expect( descriptionPageElements.some( ( element ) => element.id === "a" ) ).toBe( true );
		expect( descriptionPageElements.some( ( element ) => element.id === "b" ) ).toBe( true );
		expect( descriptionPageElements.some( ( element ) => element.id === "c" ) ).toBe( true );
		expect( descriptionPageElements.every( ( element ) => element.id !== "d" ) ).toBe( true );
		expect( descriptionPageElements.every( ( element ) => element.id !== "e" ) ).toBe( true );

		expect( descriptionPageElements.every( ( element ) => element.descriptionPage ) ).toBe( true );
		expect( descriptionPageElements.find( ( element ) => element.id === "a" ) ).toMatchObject( {
			descriptionPage: {
				dbKey: "process/a",
				isNew: false,
				exists: true
			}
		} );
		expect( descriptionPageElements.find( ( element ) => element.id === "b" ) ).toMatchObject( {
			descriptionPage: {
				dbKey: "process/b",
				isNew: true,
				exists: true
			}
		} );
		expect( descriptionPageElements.find( ( element ) => element.id === "c" ) ).toMatchObject( {
			descriptionPage: {
				dbKey: "process/c",
				isNew: false,
				exists: false
			}
		} );
	} );

	test( "addConnections", () => {
		const registry = new ElementRegistry( eventBus );
		const factory = new CpdElementFactory( registry, "process", descriptionPages );

		const a = createElementFixture( "a", "task" );
		const b = createElementFixture( "b", "dummy" );
		const c = createElementFixture( "c", "dummy" );
		const d = createElementFixture( "d", "task" );
		let outgoingConnection = {
			source: a,
			target: b
		} as unknown as Connection;
		a.outgoing = [ outgoingConnection ];
		outgoingConnection = {
			source: b,
			target: c
		} as unknown as Connection;
		b.outgoing = [ outgoingConnection ];
		outgoingConnection = {
			source: c,
			target: d
		} as unknown as Connection;
		c.outgoing = [ outgoingConnection ];

		registry.add( a, createSvgFixture() );
		registry.add( b, createSvgFixture() );
		registry.add( c, createSvgFixture() );
		registry.add( d, createSvgFixture() );

		const elements = factory.createElements();
		expect( elements ).toHaveLength( 2 );
		elements.forEach( ( cpdElement: CpdElement ) => {
			if ( cpdElement.id === 'a' ) {
				expect( cpdElement.outgoingLinks ).toHaveLength( 1 );
				expect( cpdElement.outgoingLinks[ 0 ].id ).toBe( 'd' );
			}
			if ( cpdElement.id === 'd' ) {
				expect( cpdElement.outgoingLinks ).toHaveLength( 0 );
			}
		} )
	} );
} );

const createElementFixture = ( label: string, type: string = "" ): Element => {
	return {
		id: label,
		type: type,
		businessObject: {
			name: label
		},
		incoming: [],
		outgoing: []
	} as unknown as Element;
};

const createSvgFixture = (): SVGElement => {
	return document.createElementNS( "http://www.w3.org/2000/svg", "svg" );
};
