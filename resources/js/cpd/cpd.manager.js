( function( mw, BpmnJS, customMenuModule ) {

	mw.cpdManager = {

		separator: '/',

		tplData: {
			'loading_diagram_msg': mw.message( 'cpd-loading-diagram' ),
			'err_display_diagram_msg': mw.message( 'cpd-err-display-diagram' ),
			'error_log': mw.message( 'cpd-err-details' ),
			'bpmn_header': mw.message( 'cpd-bpmn-diagram-header' ),
			'bpmn_id_input_placeholder': mw.message( 'cpd-enter-bpmn-id-placeholder' ),
			'load_bpmn_input_placeholder': mw.message( 'cpd-load-bpmn-from-wiki-placeholder' ),
			'create_bpmn_input_placeholder': mw.message( 'cpd-create-bpmn-placeholder' ),
			'bpmn_id_placeholder': mw.message( 'cpd-bpmn-id-placeholder' ),
			'overwrite_wiki_page_question': mw.message( 'cpd-overwrite-wiki-page-question' ),
			'yes': mw.message( 'cpd-yes' ),
			'no': mw.message( 'cpd-no' ),
			'create_new_bpmn': mw.message( 'cpd-create-new-bpmn' ),
			'open_bpmn_from_local': mw.message( 'cpd-open-bpmn-from-local-file' ),
			'import_warnings': mw.message( 'cpd-err-import-warning' ),
			'show_details': mw.message( 'cpd-show-details' ),
			'you_edited_diagram': mw.message( 'cpd-you-edited-diagram' ),
			'undo_last_change': mw.message( 'cpd-undo-last-change' ),
			'download_bpmn': mw.message( 'cpd-download-bpmn' ),
			'download_svg': mw.message( 'cpd-download-svg' ),
			'keyboard_shortcuts': mw.message( 'cpd-keyboard-shortcuts' ),
			'undo': mw.message( 'cpd-keyboard-shortcuts-undo' ),
			'redo': mw.message( 'cpd-keyboard-shortcuts-redo' ),
			'select_all': mw.message( 'cpd-keyboard-shortcuts-select-all' ),
			'vscroll': mw.message( 'cpd-keyboard-shortcuts-vscroll' ),
			'hscroll': mw.message( 'cpd-keyboard-shortcuts-hscroll' ),
			'direct_editing': mw.message( 'cpd-keyboard-shortcuts-direct-editing' ),
			'lasso': mw.message( 'cpd-keyboard-shortcuts-lasso' ),
			'space': mw.message( 'cpd-keyboard-shortcuts-space' ),
			'save': mw.message( 'cpd-btn-label-save' ),
			'cancel': mw.message( 'cancel' ),
		},

		bpmnName: '',

		bpmnPagePath: '',

		bpmnSVG: '',

		bpmnXML: '',

		bpmnImgEl: null,

		editToolbar: null,

		editBtn: null,

		wrapper: null,

		placeholder: null,

		renderer: null,

		error: null,

		status: 'intro',

		dirty: false,

		svgUploadedToWiki: false,

		bpmnPostedToWiki: false,

		bpmnElementsPostedToWiki: false,

		orphanedPagesDeleted: true,

		initDiagramElements: {},

		specialPageMode: false,

		cancelCallback: null,

		sandboxMode: false,

		savingProgressDialog: null,

		windowManager: null,

		states: [ 'error', 'loading', 'loaded', 'shown', 'intro' ],

		init: function( wrapper, placeholder, canvas, bpmnName, bpmnImgEl, editToolbar, editBtn, statusBar ) {
			this.bpmnPagePath = this.bpmnName = bpmnName;

			mw.cpdSemanticForms.init();

			if ( placeholder.length ) {
				this.placeholder = placeholder;
			}

			this.wrapper = wrapper;
			this.wrapper.removeClass( 'hidden' );
			this.bpmnImgEl = bpmnImgEl;
			this.bpmnImgEl.addClass( 'hidden' );
			this.editToolbar = editToolbar;
			this.editBtn = editBtn;
			this.editToolbar.removeClass( 'hidden' );
			this.editBtn.addClass( 'hidden' );
			this.statusBar = statusBar;

			var config = {
				container: canvas,
				keyboard: { bindTo: document }
			};
			if ( this.sandboxMode === false ) {
				config.additionalModules = [ customMenuModule ];
			}

			this.renderer = new BpmnJS( config );

			this.createDiagram();
			if ( this.sandboxMode === false ) {
				this.loadBPMNFromWiki();
			}
			mw.cpdWidgets.init( wrapper );

			this.setupEventHandlers();
			var me = this;
			window.onbeforeunload = function( e ) {
				if ( me.checkDirty() ) {
					return false;
				}
			};

			this.savingProgressDialog = new ext.cpd.ProgressBarDialog( {
				size: 'small'
			} );

			this.windowManager = new OO.ui.WindowManager();
			$( document.body ).append( this.windowManager.$element );

			this.windowManager.addWindows( [ this.savingProgressDialog ] );
		},

		destroy: function() {
			if ( this.renderer !== null ) {
				this.renderer.destroy();
			}
			if ( this.wrapper !== null ) {
				if ( this.wrapper.hasClass( 'hidden' ) !== true ) {
					this.wrapper.addClass( 'hidden' );
				}
				this.wrapper.empty();
			}
			if ( this.bpmnImgEl !== null && this.bpmnImgEl.attr( 'data' ) ) {
				if ( this.bpmnImgEl.hasClass( 'hidden' ) ) {
					this.bpmnImgEl.removeClass( 'hidden' );
				}
			}
			this.hideAllEditToolbars();
			this.bpmnName = '';
			this.bpmnPagePath = '';
			this.bpmnImgEl = null;
			this.bpmnSVG = '';
			this.bpmnXML = '';
			this.wrapper = null;
			this.renderer = null;
			this.status = 'intro';
			this.error = null;
			this.dirty = false;
			this.initDiagramElements = {};
			this.orphanedPagesDeleted = true;
		},

		setupEventHandlers: function() {
			// diagram update
			this.renderer.get('eventBus').on(
				'commandStack.changed',
				function() {
					mw.cpdManager.setDirty( true );
					mw.cpdWidgets.updateUndoAlert();
					mw.cpdWidgets.exportArtifacts();
				}
			);
			// diagram has been imported
			this.renderer.get('eventBus').on(
				'import.success',
				function() {
					mw.cpdManager.setDirty( false );
					mw.cpdWidgets.updateUndoAlert();
					mw.cpdWidgets.showEditingTools();
					mw.cpdWidgets.exportArtifacts();
				}
			);

		},

		createDiagram: function() {
			mw.cpdManager.renderer.createDiagram( function( err, warnings ) {
				if ( err ) {
					mw.cpdManager.setError( err );
				} else {
					// async scale to fit-viewport (prevents flickering)
					setTimeout(function() {
						mw.cpdManager.renderer.get( 'canvas' ).zoom( 'fit-viewport' );
						mw.cpdManager.setStatus( 'shown' );
					}, 0);
				}
				mw.cpdManager.renderer.get( 'keyboard' ).bind(document);
				mw.cpdWidgets.exportArtifacts();
			});
		},

		openDiagram: function( xml ) {
			mw.cpdManager.renderer.importXML(xml, function(err, warnings) {
				if (err) {
					mw.cpdManager.setError(err);
				} else {
					// async scale to fit-viewport (prevents flickering)
					setTimeout(function() {
						mw.cpdManager.renderer.get( 'canvas' ).zoom( 'fit-viewport' );
						mw.cpdManager.setStatus( 'shown' );
					}, 0);

					mw.cpdManager.setStatus( 'loaded' );
					mw.cpdWidgets.exportArtifacts();
				}
				mw.cpdWidgets.showWarnings( warnings );
			});
		},

		getCurrentDiagramElements: function() {
			if ( this.renderer !== null ) {
				return this.renderer.get( 'elementRegistry' )._elements;
			}
			return {};
		},

		getCurrentDiagramLanes: function() {
			var bpmnElements = this.getCurrentDiagramElements();
			var bpmnElementsKeys = Object.keys( bpmnElements );
			if ( bpmnElementsKeys.length > 0 ) {
				var lanes = {};
				for ( var i = 0; i < bpmnElementsKeys.length; i++ ) {
					if ( bpmnElements[bpmnElementsKeys[i]].element.type === 'bpmn:Lane' ) {
						lanes[bpmnElementsKeys[i]] = bpmnElements[bpmnElementsKeys[i]];
					}
				}
				return lanes;
			}
			return null;
		},

		getCurrentDiagramGroups: function() {
			var bpmnElements = this.getCurrentDiagramElements();
			var bpmnElementsKeys = Object.keys( bpmnElements );
			if ( bpmnElementsKeys.length > 0 ) {
				var groups = {};
				for ( var i = 0; i < bpmnElementsKeys.length; i++ ) {
					if ( bpmnElements[bpmnElementsKeys[i]].element.type === 'bpmn:Group' ) {
						groups[bpmnElementsKeys[i]] = bpmnElements[bpmnElementsKeys[i]];
					}
				}
				return groups;
			}
			return null;
		},

		setError: function( err ) {
			this.setStatus( 'error' );
			this.wrapper.find('.error .error-log').val( err.message );
		},

		setStatus: function( state ) {
			this.status = state;
			$( document.body ).removeClass( this.states.join(' ') ).addClass( state );
		},

		setDirty: function ( isDirty ) {
			this.dirty = isDirty;
		},

		checkDirty: function() {
			if ( this.dirty === true ) {
				return mw.msg('cpd-warning-message-lost-data');
			}
			return false;
		},

		/**
		 * Do some specific replacements in SVG content:
		 * * Wrap BPMN elements into links (<a xlink:href="...">) to corresponding elements' wiki pages
		 *
		 * @returns {jQuery.Promise}
		 */
		doSVGReplacements: function() {
			var dfd = $.Deferred();

			var bpmnElements = this.getCurrentDiagramElements();
			var bpmnElementsTitlesArray = Object.keys( bpmnElements ).map( function( val ) {
				return this.bpmnPagePath + '/' + val;
			}.bind( this ) );

			var bpmnTitlePromises = [];

			// MediaWiki "query" API can give information about 50 titles maximum at once
			var chunkSize = 50;
			while( bpmnElementsTitlesArray.length > 0 ) {
				var bpmnTitleChunkDeferred = $.Deferred();

				bpmnTitlePromises.push( bpmnTitleChunkDeferred.promise() );

				var bpmnTitlesChunk = bpmnElementsTitlesArray.splice( 0, chunkSize );

				var params = {
					action: 'query',
					format: 'json',
					titles: bpmnTitlesChunk.join( '|' ),
					prop: 'info',
					inprop: 'url'
				};

				var api = new mw.Api();
				api.get( params ).done( function ( bpmnTitleChunkDeferred, response ) {
					var pages = response.query.pages;

					for ( var p in pages ) {
						// In title like "CPD_diagram/Process_1" we need only the name of element - "Process_1"
						var elementName = pages[p].title.split( '/' ).pop().replace( ' ', '_' );

						var regexp = new RegExp( '(data-element-id="' + elementName + '".*?)(<g.*?\/g>)' );
						var replacement = '$1<a xlink:show="new" xlink:href="' + pages[p].fullurl + '">$2</a>';
						this.bpmnSVG = this.bpmnSVG.replace( regexp, replacement );
					}

					bpmnTitleChunkDeferred.resolve();
				}.bind( this, bpmnTitleChunkDeferred ) ).fail( function( error ) {
					dfd.reject( error );
				} );
			}

			$.when.apply( $, bpmnTitlePromises ).then( function() {
				dfd.resolve();
			} );

			return dfd.promise();
		},

		/**
		 * Uploads diagram SVG to wiki
		 *
		 * @returns {jQuery.Promise}
		 */
		uploadSVGToWiki: function() {
			var dfd = $.Deferred();
			this.svgUploadedToWiki = false;

			this.doSVGReplacements().done( function() {
				var formData = new FormData();
				formData.append( 'svgContent', this.bpmnSVG );

				var filename = encodeURIComponent( mw.cpdManager.bpmnPagePath + '.svg' );

				$.ajax( {
					method: 'POST',
					url: mw.util.wikiScript( 'rest' ) + '/cpd/save-svg/' + filename,
					data: formData,
					contentType: false,
					processData: false
				} ).done( function ( data ) {
					if ( data.success === true ) {
						this.svgUploadedToWiki = true;
						dfd.resolve( data.imageInfo );
					}
				}.bind( this ) ).fail( function ( jqXHR ) {
					console.error( jqXHR );

					var data = jqXHR.responseJSON;
					if ( data.error ) {
						dfd.reject( data );
					}
				} );
			}.bind( this ) ).fail( function( error) {
				dfd.reject( error );
			} );

			return dfd.promise();
		},

		updateBpmnImgEl: function( imageinfo ) {
			if ( mw.cpdManager.bpmnImgEl !== null ) {

				if ( this.placeholder !== null ) {
					if ( !this.placeholder.hasClass( 'hidden' ) ) {
						this.placeholder.addClass( 'hidden' );
					}
				}

				if ( mw.cpdManager.bpmnImgEl.hasClass('hidden') ) {
					mw.cpdManager.bpmnImgEl.removeClass('hidden');
				}
				if ( imageinfo ) {
					mw.cpdManager.bpmnImgEl.attr('data', imageinfo.url + '?ts=' + imageinfo.timestamp);
					mw.cpdManager.bpmnImgEl.attr('height', imageinfo.height);
					mw.cpdManager.bpmnImgEl.attr('width', imageinfo.width);
				}

				// Needed to update HTML <object>, to reload it with the latest diagram image
				mw.cpdManager.bpmnImgEl.attr( 'data', mw.cpdManager.bpmnImgEl.attr( 'data' ) );
			}
		},

		/**
		 * Posts diagram to wiki.
		 *
		 * @returns {jQuery.Promise}
		 */
		postDiagramToWiki: function() {
			this.bpmnPostedToWiki = false;
			var wikiContent = mw.cpdMapper.mapDiagramXmlToWiki(
				this.bpmnXML,
				this.bpmnPagePath,
				this.bpmnName,
				this.getCurrentDiagramElements()
			);

			return new mw.Api().post( {
				action: 'edit',
				title: mw.cpdManager.bpmnPagePath,
				text: wikiContent,
				token: mw.user.tokens.get('csrfToken')
			} );
		},

		postDiagramElementsToWiki: function() {
			mw.cpdManager.bpmnElementsPostedToWiki = false;
			var diagramLanes = this.getCurrentDiagramLanes();
			var diagramGroups = this.getCurrentDiagramGroups();
			var bpmnElements = this.getCurrentDiagramElements();

			var elementsToSave = [];
			Object.keys( bpmnElements ).forEach( function( k ) {
				var content = mw.cpdMapper.mapElementToWikiContent(
					bpmnElements[k],
					mw.cpdManager.bpmnPagePath,
					diagramLanes,
					diagramGroups
				);

				var elementPageName = mw.cpdManager.bpmnPagePath + mw.cpdManager.separator + bpmnElements[k].element.id;

				elementsToSave.push( {
					content: content,
					title: elementPageName
				} );
			} );

			var dfd = $.Deferred();

			new mw.Api().post( {
				action: 'cpd-save-diagram-elements',
				elements: JSON.stringify( elementsToSave ),
				token: mw.user.tokens.get('csrfToken')
			} ).done( function( result ) {
				var processId = result.processId;

				var failedStatusCalls = 0;

				var timer = setInterval( function() {
					$.ajax( {
						method: 'GET',
						url: mw.util.wikiScript( 'rest' ) + '/cognitiveprocessdesigner/save_elements/status/{0}'.format( processId ),
						data: {},
						contentType: 'application/json',
						dataType: 'json'
					} ).done( function( result ) {
						if ( result.state === 'terminated' ) {
							if ( result.exitCode === 0 ) {
								clearInterval( timer );

								dfd.resolve();
							} else {
								clearInterval( timer );

								dfd.reject( result.exitStatus );
							}
						}
					} ).fail( function( result ) {
						console.dir( result );

						// If requests are constantly failing - then probably API is unreachable currently
						if ( failedStatusCalls > 5 ) {
							clearInterval( timer );
							dfd.reject( result );
						}

						failedStatusCalls++;
					} );
				}, 1500 );
			} ).fail( function( result ) {
				dfd.reject( result.error );
			} );

			return dfd.promise();
		},

		deleteOrphanedElementPagesFromWiki: function() {
			var dfd = $.Deferred();

			if ( mw.cpdManager.initDiagramElements.length > 0 ) {
				var currentBpmnElementIds = Object.keys( mw.cpdManager.getCurrentDiagramElements() );
				var elementsToDelete = [];

				mw.cpdManager.initDiagramElements.forEach( function( id ) {
					if ( currentBpmnElementIds.indexOf( id ) === -1 ) {
						elementsToDelete.push( {
							title: mw.cpdManager.bpmnPagePath + mw.cpdManager.separator + id
						} );
					}
				} );

				if ( elementsToDelete.length > 0 ) {
					new mw.Api().post( {
						action: 'cpd-delete-orphaned-elements',
						elements: JSON.stringify( elementsToDelete ),
						token: mw.user.tokens.get( 'csrfToken' )
					} ).done( function( result ) {
						dfd.resolve();
					} ).fail( function( result ) {
						dfd.reject();
					} );
				}
				else {
					dfd.resolve();
				}
			}
			else {
				dfd.resolve();
			}

			return dfd.promise();
		},

		loadBPMNFromWiki: function() {
			new mw.Api().get( {
				action: 'parse',
				prop: 'wikitext',
				format: 'json',
				page: mw.cpdManager.bpmnPagePath
			} ).done( function(data) {
				if ( data.parse.wikitext["*"] ) {
					var xml = mw.cpdMapper.mapDiagramWikiToXml( data.parse.wikitext['*'] );
					if ( xml.length > 1 ) {
						mw.cpdManager.setStatus( 'loading' );
						mw.cpdManager.openDiagram( xml );
						var waitForShown = setInterval( function() {
							if ( mw.cpdManager.status === 'shown' ) {
								mw.cpdManager.setDirty( false );
								mw.cpdManager.initDiagramElements = Object.keys( mw.cpdManager.getCurrentDiagramElements() );
								clearInterval(waitForShown);
							}
						}, 2000);
					} else {
						mw.cpdManager.setStatus( 'loaded' );
					}
				}
			}).fail( function( reqStatus, data) {
				console.warn( 'LoadBPMNFromWiki failed:' + data );
			});
		},

		editBPMN: function( bpmnName, wrapper, placeholder, imgEl, editBtn ) {
			if ( !this.actionConfirmed() ) {
				return;
			}
			this.destroy();
			var bpmnEditor = mw.template
					.get( 'ext.cognitiveProcessDesigner.editor', 'bpmneditor.mustache' )
					.render( this.tplData )
					.html();
			wrapper.append(bpmnEditor);
			var canvas = $(wrapper.find( '.canvas' )[0]);
			var editToolbar = $(wrapper.find( '.cpd-edit-bpmn-toolbar' )[0]);
			var statusBar = $(wrapper.find( '.status-bar' )[0]);

			var saveBtn = $(wrapper.find( '.cpd-save-bpmn' )[0]);
			var cancelBtn = $(wrapper.find( '.cpd-cancel-bpmn' )[0]);
			if ( this.sandboxMode === false ) {
				saveBtn.click( function( e ) {
					mw.cpdManager.saveBPMN();
				});
				cancelBtn.click( function( e ) {
					mw.cpdManager.cancelEditBPMN();
				});
			} else {
				saveBtn.hide();
				cancelBtn.hide();
			}

			this.init( wrapper, placeholder, canvas, bpmnName, imgEl, editToolbar, editBtn, statusBar );
		},

		cancelEditBPMN: function() {
			if ( !this.actionConfirmed() ) {
				return;
			}
			this.destroy();
			if ( this.cancelCallback !== null ) {
				this.cancelCallback();
			}
		},

		saveBPMN: function() {
			this.statusBar.text( mw.message( 'cpd-saving-process' ) );

			this.savingProgressDialog.open( {
				elementsAmount: Object.keys( this.getCurrentDiagramElements() ).length
			} );

			this.postDiagramToWiki().done( function() {
				this.postDiagramElementsToWiki().done( function() {
					this.deleteOrphanedElementPagesFromWiki().done( function() {
						this.uploadSVGToWiki().done( function( info ) {
							mw.cpdManager.updateBpmnImgEl( info );
							if (mw.cpdManager.specialPageMode === false) {
								mw.cpdManager.destroy();
							}
							mw.cpdManager.statusBar.text( mw.message( 'cpd-saved' ) );

							mw.cpdManager.savingProgressDialog.close();
						} ).fail( function( errorData ) {
							var errorMessage = mw.message( 'cpd-saving-error-svg-upload' ).text();

							mw.cpdManager.processSaveDiagramError( errorMessage, errorData );
						} );
					}.bind( this ) ).fail( function( errorData ) {
						var errorMessage = mw.message( 'cpd-saving-error-delete-orphaned-pages' ).text();

						mw.cpdManager.processSaveDiagramError( errorMessage, errorData );
					} );
				}.bind( this ) ).fail( function( errorData ) {
					var errorMessage = mw.message( 'cpd-saving-error-elements-save' ).text();

					mw.cpdManager.processSaveDiagramError( errorMessage, errorData );
				} );
			}.bind( this ) ).fail( function( errorData ) {
				var errorMessage = mw.message( 'cpd-saving-error-diagram-save' ).text();

				mw.cpdManager.processSaveDiagramError( errorMessage, errorData );
			} ) ;
		},

		/**
		 * Processes error during diagram saving.
		 * Closes "process" dialog, shows message box with error and clears status bar.
		 *
		 * @param {String} errorMessage Translated error message
		 * @param {String|Object} errorData Arbitrary error data
		 */
		processSaveDiagramError: function( errorMessage, errorData ) {
			mw.cpdManager.savingProgressDialog.close();

			if ( typeof errorData !== 'undefined' ) {
				console.log('Error data:');
				console.dir(errorData);
			}

			mw.cpdManager.showErrorMessage( errorMessage );

			mw.cpdManager.statusBar.text( '' );
		},

		/**
		 * Shows error message dialog.
		 * Should be used in case of some errors, for example when saving diagram
		 *
		 * @param {String} message Already translated message
		 */
		showErrorMessage: function( message ) {
			var messageDialog = new OO.ui.MessageDialog();

			this.windowManager.addWindows( [ messageDialog ] );

			this.windowManager.openWindow( messageDialog, {
				title: mw.message( 'cpd-error-dialog-title' ).text(),
				message: message,
				actions: [
					{
						action: 'accept',
						label: 'Dismiss',
						flags: 'primary'
					}
				]
			});
		},

		actionConfirmed: function() {
			var confirmAction = true;
			if ( this.checkDirty() ) {
				confirmAction = mw.confirmCloseWindow(
					{ message: mw.msg( 'cpd-warning-message-lost-data' ) }
				).trigger();
			}
			return confirmAction;
		},

		hideAllEditToolbars: function() {
			if ( this.editToolbar !== null ) {
				this.editToolbar.addClass( 'hidden' );
			}
			if ( this.editBtn !== null ) {
				this.editBtn.removeClass( 'hidden' );
			}
		}
	};

	$( '.cpd-edit-bpmn' ).each( function( el ) {
		$( this ).click( function( e ) {
			var bpmnId = e.target.dataset.id;
			var bpmnName = e.target.dataset.bpmnName;
			var wrapper = $( '#cpd-wrapper-' + bpmnId  ),
				imgEl = $( '#cpd-img-' + bpmnId ),
				editBtn = $( this ),
				placeholder = $( '#cpd-placeholder-' + bpmnId );
			mw.cpdManager.editBPMN(
				bpmnName, wrapper, placeholder, imgEl, editBtn
			);
		});
	} );

}( mw, BpmnJS, customMenuModule ) );
