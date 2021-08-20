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

		batchSize: 5,

		bpmnName: '',

		bpmnPagePath: '',

		bpmnSVG: '',

		bpmnXML: '',

		bpmnImgEl: null,

		editToolbar: null,

		editBtn: null,

		wrapper: null,

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

		states: [ 'error', 'loading', 'loaded', 'shown', 'intro' ],

		init: function( wrapper, canvas, bpmnName, bpmnImgEl, editToolbar, editBtn, statusBar ) {
			this.bpmnPagePath = this.bpmnName = bpmnName;

			mw.cpdSemanticForms.init();

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
			if ( this.bpmnImgEl !== null && this.bpmnImgEl.attr( 'src' ) ) {
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

		uploadSVGToWiki: function() {
			var dfd = $.Deferred();
			this.svgUploadedToWiki = false;
			var data = new Uint8Array( this.bpmnSVG.length );
			for ( var i = 0; i < this.bpmnSVG.length; i++ ) {
				data[i] = this.bpmnSVG.charCodeAt( i );
			}
			var blob = new Blob([data], { type: 'image/svg+xml' });
			new mw.Api().upload( blob, {
					filename: mw.cpdManager.bpmnPagePath,
					ignorewarnings: true,
					format: 'json'
			}).done( function(data) {
				if ( data.upload ) {
					dfd.resolve( data.upload.imageinfo );
				}
			}).fail( function(retStatus, data) {
				if ( data.error ) {
					if ( data.error.code === 'fileexists-no-change' ||
						data.error.code === 'internal_api_error_DBTransactionStateError' ) {
						dfd.resolve();
					}
					else {
						dfd.reject();
					}
				}
				if ( data.upload ) {
					dfd.resolve( data.upload.imageinfo );
				}
			});

			return dfd.promise();
		},

		updateBpmnImgEl: function( imageinfo ) {
			if ( mw.cpdManager.bpmnImgEl !== null ) {
				mw.cpdManager.bpmnImgEl.attr( 'src', imageinfo.url + '?ts=' + imageinfo.timestamp );
				mw.cpdManager.bpmnImgEl.parent().attr( 'href', imageinfo.descriptionurl );
				if ( mw.cpdManager.bpmnImgEl.hasClass('hidden') ) {
					mw.cpdManager.bpmnImgEl.removeClass('hidden');
				}
			}
		},

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
				token: mw.user.tokens.get('editToken')
			} );
		},

		postDiagramElementsToWiki: function() {
			mw.cpdManager.bpmnElementsPostedToWiki = false;
			var diagramLanes = this.getCurrentDiagramLanes();
			var diagramGroups = this.getCurrentDiagramGroups();
			var bpmnElements = this.getCurrentDiagramElements();

			var batches = this.makeElementsBatches( Object.values( bpmnElements ) );

			var batchDFD = $.Deferred(), dfd = $.Deferred();

			this.executeElementsBatches( batches, diagramLanes, diagramGroups, batchDFD);

			batchDFD.done( function() {
				dfd.resolve();
			} );

			return dfd.promise();
		},

		makeElementsBatches: function( elements ) {
			var batches = [];

			while( elements.length > 0 ) {
				batches.push( elements.splice( 0, this.batchSize ) );
			}

			return batches;
		},

		executeElementsBatches: function( batches, diagramLanes, diagramGroups, dfd ) {
			if( batches.length === 0 ) {
				dfd.resolve();
				return;
			}

			var batch = batches.shift();

			this.executeElementsBatch( batch, diagramLanes, diagramGroups ).done( function() {
				this.executeElementsBatches( batches, diagramLanes, diagramGroups, dfd );
			}.bind( this ) );
		},

		executeElementsBatch: function( bpmnElements, diagramLanes, diagramGroups ) {
			var dfd = $.Deferred();

			var editElementPagePromises = [];

			Object.keys( bpmnElements ).forEach( function( k ) {
				var editElementPageDeferred = $.Deferred();
				editElementPagePromises.push( editElementPageDeferred.promise() );

				var content = mw.cpdMapper.mapElementToWikiContent(
					bpmnElements[k],
					mw.cpdManager.bpmnPagePath,
					diagramLanes,
					diagramGroups
				);

				var elementPageName = mw.cpdManager.bpmnPagePath + mw.cpdManager.separator + bpmnElements[k].element.id;
				var currentPageContentAPI = new mw.Api();
				currentPageContentAPI.get( {
					prop: 'revisions',
					rvprop: 'content|timestamp',
					titles: elementPageName,
					curtimestamp: true,
					indexpageids: true
				} )
				.done( function( result ) {
					var pageId = result.query.pageids[0];
					var pageData = result.query.pages[pageId];
					var revisionContent = '';
					if ( pageData.revisions ) {
						revisionContent = pageData.revisions[0]['*'];
					}
					revisionContent = revisionContent.replace(/<div class="cpd-data".*?[\s\S]+<\/div>/, '').trim();
					revisionContent = '<div class="cpd-data">' + content + '</div>' + "\n" + revisionContent;
					var editPageAPI = new mw.Api();
					editPageAPI.postWithToken( 'csrf', {
						action: 'edit',
						title: elementPageName,
						text: revisionContent
					} )
					.done( function( result ) {
						editElementPageDeferred.resolve();
					} )
					.fail( function( error ) {
						dfd.reject( error );
					});
				} );
			});
			$.when.apply( $, editElementPagePromises ).then( function() {
				dfd.resolve();
			} );

			return dfd.promise();
		},

		deleteOrphanedElementPagesFromWiki: function() {
			var dfd = $.Deferred();
			if ( mw.cpdManager.initDiagramElements.length > 0 ) {
				var currentBpmnElementIds = Object.keys( mw.cpdManager.getCurrentDiagramElements() );
				mw.cpdManager.initDiagramElements.forEach( function( id ) {
					if ( currentBpmnElementIds.indexOf( id ) === -1 ) {
						mw.cpdManager.orphanedPagesDeleted = false;
						new mw.Api().post( {
							action: 'edit',
							title: mw.cpdManager.bpmnPagePath + mw.cpdManager.separator + id,
							text: '[[Category:Delete]]',
							token: mw.user.tokens.get('editToken')
						} ).done( function() {
							dfd.resolve();
						} ).fail( function() {
							dfd.reject();
						} );
					}
				} );
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

		editBPMN: function( bpmnName, wrapper, imgEl, editBtn ) {
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

			this.init( wrapper, canvas, bpmnName, imgEl, editToolbar, editBtn, statusBar );
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
			this.postDiagramToWiki().done( function() {
				this.postDiagramElementsToWiki().done( function() {
					this.deleteOrphanedElementPagesFromWiki().done( function() {
						this.uploadSVGToWiki().done( function( info ) {
							mw.cpdManager.updateBpmnImgEl( info );
							if (mw.cpdManager.specialPageMode === false) {
								mw.cpdManager.destroy();
							}
							mw.cpdManager.statusBar.text( mw.message( 'cpd-saved' ) );
						} ).fail( function() {
							console.error( 'Failed uploading SVG');
						} );
					}.bind( this ) ).fail( function() {
						console.error( 'Failed deleting orphaned pages');
					} );
				}.bind( this ) ).fail( function( error ) {
					console.error( 'Failed posting elements to wiki' );
				} );
			}.bind( this ) ).fail( function( error ) {
				console.error( 'Failed posting to wiki');
			} ) ;
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
				editBtn = $( this );
			mw.cpdManager.editBPMN(
				bpmnName, wrapper, imgEl, editBtn
			);
		});
	} );

}( mw, BpmnJS, customMenuModule ) );
