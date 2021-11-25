( function( mw ) {

	function Config() {
		var storage = window.localStorage || {};

		this.get = function(key) {
			return storage[key];
		};
		this.set = function(key, value) {
			storage[key] = value;
		};
	}

	mw.cpdWidgets = {

		widgets: {},

		delegates: {},

		config: null,

		init: function( wrapper ) {
			this.destroy();

			$( wrapper ).find( '[jswidget]' ).each( function () {
				var element = $(this),
					jswidget = element.attr( 'jswidget' );
				mw.cpdWidgets.widgets[jswidget] = element;
			});

			$('<input id="cpd_bpmn_file_input" type="file" />').appendTo(document.body).css({
				width: 1,
				height: 1,
				display: 'none',
				overflow: 'hidden'
			});

			$( '#cpd_bpmn_file_input' ).change( function(e) {
				mw.cpdWidgets.openFile( e.target.files[0], mw.cpdManager.openDiagram);
			} );

			$( wrapper ).find('[jsaction]').click( mw.cpdWidgets.actionListener );

			mw.cpdWidgets.widgets.downloadBPMN.click( function( e ) {
				mw.cpdManager.setDirty( false );
			});
		},

		destroy: function() {
			$( '[jsaction]' ).off();
			$( '#cpd_bpmn_file_input' ).remove();
			mw.cpdWidgets.widgets = {};
			mw.cpdWidgets.delegates = {};
			mw.cpdWidgets.config = new Config();
		},

		actions: function () {
			return {
				'bio.toggleFullscreen': function () {
					mw.cpdWidgets.toggleFullScreen( document.querySelector('.cpd-js-drop-zone') );
				},
				'bio.createNew': mw.cpdManager.createDiagram,
				'bio.openLocal': function () {
					$( '#cpd_bpmn_file_input' ).trigger('click');
				},
				'bio.zoomReset': function () {
					if (mw.cpdManager.renderer) {
						mw.cpdManager.renderer.get('zoomScroll').reset();
					}
				},
				'bio.zoomIn': function (e) {
					if (mw.cpdManager.renderer) {
						mw.cpdManager.renderer.get('zoomScroll').stepZoom(1);
					}
				},
				'bio.zoomOut': function (e) {
					if (mw.cpdManager.renderer) {
						mw.cpdManager.renderer.get( 'zoomScroll' ).stepZoom(-1);
					}
				},
				'bio.showKeyboard': function (e) {
					var dialog = mw.cpdWidgets.widgets['keybindings-dialog'];
					var platform = navigator.platform;

					if (/Mac/.test(platform)) {
						dialog.find('.bindings-default').remove();
					} else {
						dialog.find('.bindings-mac').remove();
					}

					mw.cpdWidgets.openDialog(dialog);
				},
				'bio.showAbout': function (e) {
					mw.cpdWidgets.openDialog( mw.cpdWidgets.widgets['about-dialog'] );
				},
				'bio.undo': function (e) {
					if (mw.cpdManager.renderer) {
						mw.cpdManager.renderer.get( 'commandStack' ).undo();
					}
				},
				'bio.hideUndoAlert': mw.cpdWidgets.hideUndoAlert,
				'bio.clearImportDetails': function (e) {
					mw.cpdWidgets.showWarnings(null );
				},
				'bio.showImportDetails': function (e) {
					var details = mw.cpdWidgets.widgets['import-warnings-alert'].find('.details');
					if ( details.length !== 0 ) {
						mw.cpdWidgets.toggleVisible( details, true );
					}
				}
			};
		},

		showEditingTools: function() {
			mw.cpdWidgets.widgets['editing-tools'].show();
		},

		updateUndoAlert: function( idx ) {
			if ( mw.cpdWidgets.config.get( 'hide-alert' ) ) {
				return;
			}
			mw.cpdWidgets.toggleVisible(
				mw.cpdWidgets.widgets['undo-redo-alert'],
				idx >= 0
			);
		},

		hideUndoAlert: function( e ) {
			if ( mw.cpdManager.renderer ) {
				mw.cpdWidgets.toggleVisible( mw.cpdWidgets.widgets['undo-redo-alert'], false );
				mw.cpdWidgets.config.set( 'hide-alert', 'yes' );
			}
		},

		openDialog: function(dialog) {
			var content = dialog.find( '.content' );
			mw.cpdWidgets.toggleVisible( dialog, true );
			function stop( e ) {
				e.stopPropagation();
			}
			function hide( e ) {
				mw.cpdWidgets.toggleVisible( dialog, false );
				dialog.off( 'click', hide );
				content.off( 'click', stop );
			}
			content.on( 'click', stop );
			dialog.on( 'click', hide );
		},

		toggleVisible: function( element, show ) {
			element[show ? 'addClass' : 'removeClass']('open');
		},

		toggleFullScreen: function( element ) {
			if (!document.fullscreenElement &&
				!document.mozFullScreenElement &&
				!document.webkitFullscreenElement &&
				!document.msFullscreenElement
			) {
				if ( element.requestFullscreen ) {
					element.requestFullscreen();
				} else if ( element.msRequestFullscreen ) {
					element.msRequestFullscreen();
				} else if ( element.mozRequestFullScreen ) {
					element.mozRequestFullScreen();
				} else if ( document.documentElement.webkitRequestFullscreen ) {
					element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
				}
			} else {
				if ( document.exitFullscreen ) {
					document.exitFullscreen();
				} else if ( document.msExitFullscreen ) {
					document.msExitFullscreen();
				} else if ( document.mozCancelFullScreen ) {
					document.mozCancelFullScreen();

				} else if ( document.webkitExitFullscreen ) {
					document.webkitExitFullscreen();
				}
			}
		},

		openFile: function( file, callback ) {
			// check file api availability
			if (!window.FileReader) {
				return window.alert(
					'Looks like you use an older browser that does not support drag and drop. ' +
					'Try using a modern browser such as Chrome, Firefox or Internet Explorer > 10.');
			}
			// no file chosen
			if ( !file ) {
				return;
			}
			mw.cpdManager.setStatus( 'loading' );
			var reader = new FileReader();
			reader.onload = function( e ) {
				var xml = e.target.result;
				callback( xml );
			};
			reader.readAsText(file);
		},

		showWarnings: function( warnings ) {
			var show = warnings && warnings.length;
			mw.cpdWidgets.toggleVisible( mw.cpdWidgets.widgets['import-warnings-alert'], show );
			if ( !show ) {
				return;
			}
			console.warn( 'imported with warnings' );
			var messages = '';
			warnings.forEach( function( w ) {
				console.warn( w );
				messages += ( w.message + '\n\n' );
			});
			var dialog = mw.cpdWidgets.widgets['import-warnings-alert'];
			dialog.find( '.error-log' ).val( messages );
		},

		parseActionAttr: function( element ) {
			var match = $( element ).attr( 'jsaction' ).split(/:(.+$)/, 2);
			return {
				event: match[0], // click
				name: match[1] // bio.fooBar
			};
		},

		actionListener: function( event ) {
			var jsaction = mw.cpdWidgets.parseActionAttr($(this));
			var name = jsaction.name,
				action = mw.cpdWidgets.actions()[name];

			if (!action) {
				throw new Error( 'no action <' + name + '> defined' );
			}

			event.preventDefault();
			action(event);
		},

		exportArtifacts: function() {
			mw.cpdManager.renderer.saveSVG( function( err, svg ) {
				mw.cpdManager.bpmnSVG = svg;
				mw.cpdWidgets.setEncoded(
					mw.cpdWidgets.widgets.downloadSVG,
					'diagram.svg',
					err ? null : svg
				);
			});
			mw.cpdManager.renderer.saveXML( { format: true }, function( err, xml ) {
				mw.cpdManager.bpmnXML = xml;
				mw.cpdWidgets.setEncoded(
					mw.cpdWidgets.widgets.downloadBPMN,
					'diagram.bpmn',
					err ? null : xml
				);
			});
		},

		setEncoded: function ( link, name, data ) {
			if (data) {
				link.attr({
					'href': 'data:application/bpmn20-xml;charset=UTF-8,' + encodeURIComponent(data),
					'download': name
				}).removeClass('inactive');
			} else {
				link.addClass('inactive');
			}
		}
	};
}( mediaWiki ) );
