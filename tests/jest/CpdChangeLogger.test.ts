import CpdChangeLogger from "../../resources/js/cpd/helper/CpdChangeLogger";
import EventBus from "diagram-js/lib/core/EventBus";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";
import { CpdElementFactory } from "../../resources/js/cpd/helper/CpdElementFactory";
import CpdInlineSvgRenderer from "../../resources/js/cpd/helper/CpdInlineSvgRenderer";

describe( "createLinkFromDbKey", () => {
	const eventBus = new EventBus();
	const registry = new ElementRegistry( eventBus );
	const factory = new CpdElementFactory( registry, "process", [
		"process/a",
		"process/b"
	] );
	const svgRenderer = new CpdInlineSvgRenderer( registry );

	const cpdXml = new CpdChangeLogger( eventBus, factory, svgRenderer );
	test.each( [
		[ "Process:Foo/Parent_1/task_1",  '<a target="_blank" href="/jest/index.php?title=Process:Foo/Parent_1/task_1">Parent 1/task 1</a>' ],
		[ "Process:Foo/Parent_1/Parent_2/Parent_3/task_1", '<a target="_blank" href="/jest/index.php?title=Process:Foo/Parent_1/Parent_2/Parent_3/task_1">Parent 1/Parent 2/Parent 3/task 1</a>' ],
		[ "Process:Foo/task_1", '<a target="_blank" href="/jest/index.php?title=Process:Foo/task_1">task 1</a>' ],
		[ "Parent_1/task_1", null ],
		[ "task_1", null ],
		[ "", null ],
		[ null, null ],
	] )( "createLinkFromDbKey", ( dbKey: string, expectLink: string ) => {
		const link = cpdXml.createLinkFromDbKey( dbKey );
		expect( link ).toEqual( expectLink );
	} );
} );
