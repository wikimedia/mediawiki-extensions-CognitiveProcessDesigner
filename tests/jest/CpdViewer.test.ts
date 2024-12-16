import CpdViewer from "../../resources/js/cpd/CpdViewer";

jest.mock("../../resources/js/cpd/helper/CpdDom");
jest.mock("../../resources/js/cpd/helper/CpdApi");
jest.mock("../../resources/js/cpd/helper/CpdXml");

describe( "CpdViewer", () => {
	const container = document.createElement( "div" );
	const cpdViewer = new CpdViewer( 'process', container );

	test( "CpdViewer", () => {
		expect( cpdViewer ).toBeTruthy();
	} );
} );
