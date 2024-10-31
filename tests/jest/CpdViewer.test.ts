import CpdViewer from "../../resources/js/cpd/CpdViewer";

jest.mock("../../resources/js/cpd/helper/CpdDom");
jest.mock("../../resources/js/cpd/helper/CpdApi");
jest.mock("../../resources/js/cpd/helper/CpdXml");

describe( "CpdViewer", () => {
	const cpdViewer = new CpdViewer( 'process' );

	test( "CpdViewer", () => {
		expect( cpdViewer ).toBeTruthy();
	} );
} );
