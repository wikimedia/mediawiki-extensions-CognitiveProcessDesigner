class CustomContextPad {
	constructor(config, contextPad, create, elementFactory, injector, translate, contextPadProvider, replaceMenuProvider) {
		this.create = create;
		this.elementFactory = elementFactory;
		this.translate = translate;
		this.contextPadProvider = contextPadProvider;
		this.replaceMenuProvider = replaceMenuProvider;
		this.popupMenu = contextPadProvider._popupMenu;

		if (config.autoPlace !== false) {
			this.autoPlace = injector.get('autoPlace', false);
		}

		contextPad.registerProvider(this);
	}

	getContextPadEntries(element) {
		var me = this;

		window.popupMenu = this.popupMenu;

		function openAnnotate( position ) {
			if (!position) {
				throw new Error('the position argument is missing');
			}

			if (me.popupMenu.isOpen()) {
				me.popupMenu.close();
			}

			var current = me.popupMenu._current,
				canvas = me.popupMenu._canvas,
				parent = canvas.getContainer();

			current.position = position;

			current.container = createAnnotateContainer();
			// current.container = me.popupMenu._createContainer();
			var headerEntries = [];

			var headTitle = {className: 'headerAnnotate', id: 'bpmn-annotate', options: '', title:'Annotate Element'};
			headerEntries.push(headTitle);

			var headerEntriesContainer = me.popupMenu._createEntries(headerEntries, 'djs-popup-header');
			current.container.appendChild(headerEntriesContainer);

			loadSemanticForms('');

			var entriesContainer = me.popupMenu._createEntries(annotate, 'djs-popup-body');
			current.entries = annotate;
			current.container.appendChild(entriesContainer);

			me.popupMenu._attachContainer(current.container, parent, position.cursor);

			document.addEventListener('click', function(event) {
				// If user clicks inside popup, do nothing
				if (
					$(event.target).closest( '.djs-popup-Annotate' )[0] !== undefined ||
					$(event.target).closest('.cpd-icon-annotate')[0] !== undefined
				) {
					return;
				}
				window.popupMenu.close();
			} );

			return me.popupMenu;
		}

		function createAnnotateContainer() {
			var container = window.document.createElement('div'),
				position = me.popupMenu._current.position,
				className = 'bpmn-replace';

			container.classList.add( 'djs-popup-Annotate' );

			Object.assign( container.style, {
				position: 'absolute',
				left: position.x + 'px',
				top: position.y + 'px',
				visibility: 'hidden'
			} );

			container.classList.add( className );
			return container;
		}

		var menuEntries = {
			'jumpToPage': {
					group: 'jumpToPage',
					className: 'cpd-icon-jumpToPage',
					title: 'Jump to Page',
					action: {
						click: function(event, element) {
							window.open( mw.config.get( 'wgScriptPath' ) + '/index.php?title=' + element.id );
						}
					}
				}
		};

		Object.assign(menuEntries, {
			'annotate': {
				group: 'annotate',
				className: 'cpd-icon-annotate',
				title: 'Page Forms',
				action: {
					click: function(event, element) {
						window.currentElement = element;
						openAnnotate( { cursor: { x: event.x, y: event.y } } );
					}
				}
			}
		});

		return menuEntries;

	}
}

CustomContextPad.$inject = [
	'config',
	'contextPad',
	'create',
	'elementFactory',
	'injector',
	'translate',
	'contextPadProvider',
	'replaceMenuProvider'
];

var customMenuModule = {
	__init__: [
		'custContextPad',
	],
	custContextPad: [ 'type', CustomContextPad ],
};

