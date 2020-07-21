var container = $('#js-drop-zone'),
	canvas = $('#js-canvas'),
	dirty = false;

function setDirty(newDirty) {
	dirty = newDirty;
}

function checkDirty() {
	if (dirty) {
		return 'The changes you performed on the diagram will be lost upon navigation.';
	}
}

window.onbeforeunload = checkDirty;

function Config() {

	var storage = window.localStorage || {};

	this.get = function(key) {
		return storage[key];
	};

	this.set = function(key, value) {
		storage[key] = value;
	};
}

var config = new Config();

var bpmnConfig = {
	container: canvas,
	keyboard: { bindTo: document }
};
if ( editMode === true ) {
	bpmnConfig.additionalModules = [ customMenuModule ];
}

window.renderer = new BpmnJS( bpmnConfig );

var states = [ 'error', 'loading', 'loaded', 'shown', 'intro' ];

function setStatus(status) {
	$(document.body).removeClass(states.join(' ')).addClass(status);
}

function setError(err) {
	setStatus('error');

	container.find('.error .error-log').val(err.message);

	console.error(err);
}

function showWarnings(warnings) {

	var show = warnings && warnings.length;

	toggleVisible(widgets['import-warnings-alert'], show);

	if (!show) {
		return;
	}

	console.warn('imported with warnings');

	var messages = '';

	warnings.forEach(function(w) {
		console.log(w);
		messages += (w.message + '\n\n');
	});

	var dialog = widgets['import-warnings-alert'];

	dialog.find('.error-log').val(messages);
}

function toggleVisible(element, show) {
	element[show ? 'addClass' : 'removeClass']('open');
}

function openDialog(dialog) {

	var content = dialog.find('.content');

	toggleVisible(dialog, true);

	function stop(e) {
		e.stopPropagation();
	}

	function hide(e) {

		toggleVisible(dialog, false);

		dialog.off('click', hide);
		content.off('click', stop);
	}

	content.on('click', stop);
	dialog.on('click', hide);
}

function openDiagram(xml) {
	window.renderer.importXML(xml, function(err, warnings) {
		if (err) {
			setError(err);
		} else {

			// async scale to fit-viewport (prevents flickering)
			setTimeout(function() {
				window.renderer.get('canvas').zoom('fit-viewport');
				setStatus('shown');
			}, 0);

			setStatus('loaded');

			exportArtifacts();
		}

		showWarnings(warnings);
	});
}

function createDiagram() {

	window.renderer.createDiagram(function(err, warnings) {

		if (err) {
			setError(err);
		} else {
			// async scale to fit-viewport (prevents flickering)
			setTimeout(function() {
				window.renderer.get('canvas').zoom('fit-viewport');
				setStatus('shown');
			}, 0);

			setStatus('loaded');
		}

		if (warnings && warnings.length) {
			console.warn('[import]', warnings);
		}
		var keyboard = window.renderer.get('keyboard');
		keyboard.bind(document);
	});
}

function saveSVG(done) {
	window.renderer.saveSVG(done);
}

function saveDiagram(done) {
	window.renderer.saveXML({ format: true }, function(err, xml) {
		done(err, xml);
	});
}

////// file drag / drop ///////////////////////

function openFile(file, callback) {

	// check file api availability
	if (!window.FileReader) {
		return window.alert(
			'Looks like you use an older browser that does not support drag and drop. ' +
			'Try using a modern browser such as Chrome, Firefox or Internet Explorer > 10.');
	}

	// no file chosen
	if (!file) {
		return;
	}

	setStatus('loading');
	importMarker = true;
	var reader = new FileReader();

	reader.onload = function(e) {

		var xml = e.target.result;

		callback(xml);
	};

	reader.readAsText(file);
}

(function onFileDrop(container, callback) {

	function handleFileSelect(e) {
		e.stopPropagation();
		e.preventDefault();
		var files = e.dataTransfer.files;
		openFile(files[0], callback);
	}

	function handleDragOver(e) {
		e.stopPropagation();
		e.preventDefault();

		e.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
	}

	document.documentElement.addEventListener('dragover', handleDragOver, false);
	document.documentElement.addEventListener('drop', handleFileSelect, false);

})(container, openDiagram);


var fileInput = $('<input type="file" />').appendTo(document.body).css({
	width: 1,
	height: 1,
	display: 'none',
	overflow: 'hidden'
}).on('change', function(e) {
	openFile(e.target.files[0], openDiagram);
});

var widgets = {};

function hideUndoAlert(e) {
	if (window.renderer) {
		toggleVisible(widgets['undo-redo-alert'], false);

		if (config.set('hide-alert', 'yes')) {
			return;
		}
	}
}

function updateUndoAlert() {
	if (config.get('hide-alert')) {
		return;
	}

	var commandStack = window.renderer.get('commandStack');

	var idx = commandStack._stackIdx;

	toggleVisible(widgets['undo-redo-alert'], idx >= 0);
}

function showEditingTools() {
	widgets['editing-tools'].show();
}

function toggleFullScreen(element) {

	if (!document.fullscreenElement &&
		!document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {

		if (element.requestFullscreen) {
			element.requestFullscreen();
		} else if (element.msRequestFullscreen) {
			element.msRequestFullscreen();
		} else if (element.mozRequestFullScreen) {
			element.mozRequestFullScreen();
		} else if (document.documentElement.webkitRequestFullscreen) {
			element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
		}
	} else {
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();

		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		}
	}
}


function undo(e) {
	if (window.renderer) {
		window.renderer.get('commandStack').undo();
	}
}

var actions = {
	'bio.toggleFullscreen': function() {
		var elem = document.querySelector('html');
		toggleFullScreen(elem);
	},
	'bio.createNew': createDiagram,
	'bio.openLocal': function() {
		$(fileInput).trigger('click');
	},
	'bio.zoomReset': function() {
		if (window.renderer) {
			window.renderer.get('zoomScroll').reset();
		}
	},
	'bio.zoomIn': function(e) {
		if (window.renderer) {
			window.renderer.get('zoomScroll').stepZoom(1);
		}
	},
	'bio.zoomOut': function(e) {
		if (window.renderer) {
			window.renderer.get('zoomScroll').stepZoom(-1);
		}
	},
	'bio.showKeyboard': function(e) {

		var dialog = widgets['keybindings-dialog'];

		var platform = navigator.platform;

		if (/Mac/.test(platform)) {
			dialog.find('.bindings-default').remove();
		} else {
			dialog.find('.bindings-mac').remove();
		}

		openDialog(dialog);
	},
	'bio.showAbout': function(e) {
		openDialog(widgets['about-dialog']);
	},
	'bio.undo': undo,
	'bio.hideUndoAlert': hideUndoAlert,
	'bio.clearImportDetails': function(e) {
		showWarnings(null);
	},
	'bio.showImportDetails': function(e) {
		openDialog(widgets['import-warnings-alert']);
	}
};

var exportArtifacts;
var delegates = {};

// initialize existing widgets defined in
// <div jswidget="nameOfWidget" />
//
// after this step we can use a widget via
// widgets['nameOfWidget']
$('[jswidget]').each(function () {
	var element = $(this),
		jswidget = element.attr('jswidget');

	widgets[jswidget] = element;
});

$('[jsaction]').each(function () {

	var jsaction = parseActionAttr($(this));

	var event = jsaction.event;

	if (!delegates[event]) {
		$(document.body).on(event, '[jsaction]', actionListener);
		delegates[event] = true;
	}


	var name = jsaction.name,
		handler = actions[name];

	if (!handler) {
		throw new Error('no action <' + name + '> defined');
	}
});

widgets.downloadBPMN.click(function(e) {
	setDirty(false);
});

// attach all the actions defined via
// <div jsaction="event:actionName" />

function parseActionAttr(element) {

	var match = $(element).attr('jsaction').split(/:(.+$)/, 2);

	return {
		event: match[0], // click
		name: match[1] // bio.fooBar
	};
}

function actionListener(event) {
	var jsaction = parseActionAttr($(this));

	var name = jsaction.name,
		action = actions[name];

	if (!action) {
		throw new Error('no action <' + name + '> defined');
	}

	event.preventDefault();

	action(event);
}
function setEncoded(link, name, data) {
	var encodedData = encodeURIComponent(data);

	if (data) {
		link.attr({
			'href': 'data:application/bpmn20-xml;charset=UTF-8,' + encodedData,
			'download': name
		}).removeClass('inactive');
	} else {
		link.addClass('inactive');
	}
}
function debounce(fn, timeout) {
	var timer;
	var lastArgs;
	var lastThis;
	var lastNow;

	function fire() {
		var now = Date.now();
		var scheduledDiff = lastNow + timeout - now;

		if (scheduledDiff > 0) {
			return schedule(scheduledDiff);
		}

		fn.apply(lastThis, lastArgs);
		timer = lastNow = lastArgs = lastThis = undefined;
	}

	function schedule(timeout) {
		timer = setTimeout(fire, timeout);
	}

	return function () {
		lastNow = Date.now();

		for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
			args[_key] = arguments[_key];
		}

		lastArgs = args;
		lastThis = this; // ensure an execution is scheduled

		if (!timer) {
			schedule(timeout);
		}
	};
}

exportArtifacts = debounce(function() {
	saveSVG(function(err, svg) {
		setEncoded(widgets.downloadSVG, 'diagram.svg', err ? null : svg);
	});
	saveDiagram(function(err, xml) {
		setEncoded(widgets.downloadBPMN, 'diagram.bpmn', err ? null : xml);
		if ( typeof updateBPMNWikiPage === "function" ) {
			var shapeList = [];
			if(Array.isArray(window.renderer.getDefinitions().rootElements)) {
				for (var i = 0; i < window.renderer.getDefinitions().rootElements.length; i++) {
					if (Array.isArray(window.renderer.getDefinitions().rootElements[i].flowElements)) {
						for (var j = 0; j < window.renderer.getDefinitions().rootElements[i].flowElements.length; j++) {
							shapeList.push(window.renderer.getDefinitions().rootElements[i].flowElements[j].id);
						}
					}
				}
			}
			if(shapeList.length > 0) {
				updateBPMNWikiPage(shapeList, xml);
			}
		}

		if (config.get('save')) {
			config.set('save.diagramXML', xml);
		}
	});
}, 500);

function modelUpdate() {
	
	setDirty(true);

	updateUndoAlert();

	exportArtifacts();
}

function importSuccess() {
	
	setDirty(false);

	updateUndoAlert();
	showEditingTools();
	exportArtifacts();
}

// editMode defined in CognitiveProcessDesign.mustache template
if ( editMode === true ) {
	// update diagram on every action
	window.renderer.get('eventBus').on( 'commandStack.changed', modelUpdate);
	window.renderer.get('eventBus').on( 'import.success', importSuccess);

// shape events
	window.renderer.get('eventBus').on(
		'commandStack.shape.create.executed',
		function( err, context ) {
			
			if ( context.context.shape === undefined ) {
				console.log( 'A shape is undefined' );
			} else {
				createWikiShape( context.context.shape );
			}
		}
	);

	window.renderer.get('eventBus').on(
		'commandStack.shape.replace.preExecuted',
		function( err, context ) {
			if ( context.context.oldShape === undefined || context.context.newShape === undefined ) {
				console.log( 'A shape is undefined' );
			} else {
				replace = 1;
				replaceShapeFromWiki(context.context.oldShape, context.context.newShape);
			}
		}
	);

	window.renderer.get('eventBus').on(
		'commandStack.shape.replace.postExecuted',
		function( err, context ) {
			replace = 0;
		}
	);

	window.renderer.get('eventBus').on(
		'shape.move.end',
		function( err, context ) {
			if ( context.shape === undefined ) {
				console.log( 'A shape is undefined' );
			} else {
				moveShape(context.shape);
			}
		}
	);

	window.renderer.get('eventBus').on(
		'shape.removed',
		function( err, context ) {
			if ( window.importDiagram === true ) {
				return;
			}
			var shape = context.element;
			if ( shape !== undefined && shape.id !== undefined ) {
				if (shape.id.indexOf('_label') === -1) {
					shapeToBeDeleted = shape.id;
				}
				var parent = shape;
				var shapeList = [];

				while ( parent.parent !== null ) {
					parent = parent.parent;
				}
				shapeList = getShapeListDelete( parent, shapeList, shapeToBeDeleted );
				writeWikiContent( '[[Category:Delete]]', parent.id );
				if ( shape.id.indexOf( '_label' ) === -1 ) {
					updateBPMNWikiPage( shapeList );
				}
			}
		}
	);

// connection events

	window.renderer.get('eventBus').on(
		'commandStack.connection.create.executed',
		function( err, context ) {
			if (
				context.context.connection === undefined ||
				context.context.source === undefined ||
				context.context.target === undefined
			) {
				console.log( 'A connection/target/source is undefined' );
			} else {
				addConnection(context.context.source, context.context.target, context.context.connection);
			}
		}
	);

	window.renderer.get('eventBus').on(
		'commandStack.connection.layout.executed',
		function( err, context ) {
			if ( context.context.connection === undefined ) {
				console.log('A connection is undefined');
			} else {
				updateConnection(context.context.connection);
			}
		}
	);

	window.renderer.get('eventBus').on(
		'commandStack.connection.updateWaypoints.executed',
		function( err, context ) {
			if ( context.context.connection === undefined ) {
				console.log('A connection is undefined');
			} else {
				updateConnection(context.context.connection);
			}
		}
	);

	window.renderer.get('eventBus').on(
		'commandStack.connection.reconnect.executed',
		function( err, context ) {
			if (
				context.context.connection === undefined ||
				context.context.oldTarget === undefined ||
				context.context.newTarget === undefined
			) {
				console.log('A connection/target is undefined');
			} else {
				updateConnection(context.context.connection);
				updateShape(context.context.oldTarget);
				updateShape(context.context.newTarget);
			}
		}
	);

	window.renderer.get('eventBus').on(
		'connection.removed',
		function( err, context ) {
			writeWikiContent('[[Category:Delete]]', context.element.id);
			updateShape(context.element.source);
			updateShape(context.element.target);
		}
	);
}

/**
 * Open or load diagram
 */
(function() {

	var href = window.location.href;

	var loader = null;

	// Open diagram via /s/:diagramName

	var openRemoteMatch = /\/s\/(.*)/.exec(href);

	if (openRemoteMatch) {
		loader = function() {
			var req = $.ajax('/bpmn/diagrams/' + openRemoteMatch[1] + '.bpmn', { dataType: 'text' });

			req
				.success(function(data) {
					openDiagram(data);
				})
				.error(function(response) {
					var err;

					if (response.status === 404) {
						err = new Error('The diagram does not exist (code=404)');
					} else {
						err = new Error('Failed to load diagram (code=' + response.code + ')');
					}

					setError(err);
				});

		};

	} else

	// Create a new diagram on /new
	loader = createDiagram;


	// Load diagram via localStorage, if specified.
	if (config.get('save')) {

		var diagramXML = config.get('save.diagramXML');

		if (diagramXML) {
			loader = function() {
				openDiagram(diagramXML);
			};
		}
	}

	if (loader) {
		setStatus('loading');
		setTimeout(loader, 100);
	}
})();