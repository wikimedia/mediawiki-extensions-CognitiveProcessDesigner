import CpdChangeLogger from "../../resources/js/cpd/helper/CpdChangeLogger";
import EventBus from "diagram-js/lib/core/EventBus";
import ElementRegistry from "diagram-js/lib/core/ElementRegistry";
import { CpdElementFactory } from "../../resources/js/cpd/helper/CpdElementFactory";
import CpdInlineSvgRenderer from "../../resources/js/cpd/helper/CpdInlineSvgRenderer";
import CpdLinker from "../../resources/js/cpd/helper/CpdLinker";

describe( "createLinkFromDbKey", () => {
	test.each( [
		[ "Process:Foo/Parent_1/task_1", '<a target="_blank" href="/jest/index.php?title=Process:Foo/Parent_1/task_1">Parent 1/task 1</a>' ],
		[ "Process:Foo/Parent_1/Parent_2/Parent_3/task_1", '<a target="_blank" href="/jest/index.php?title=Process:Foo/Parent_1/Parent_2/Parent_3/task_1">Parent 1/Parent 2/Parent 3/task 1</a>' ],
		[ "Process:Foo/task_1", '<a target="_blank" href="/jest/index.php?title=Process:Foo/task_1">task 1</a>' ],
		[ "Parent_1/task_1", null ],
		[ "task_1", null ],
		[ "", null ],
		[ null, null ],
	] )( "createLinkFromDbKey", ( dbKey: string, expectLink: string ) => {
		const link = CpdLinker.createLinkFromDbKey( dbKey );
		expect( link ).toEqual( expectLink );
	} );
} );
